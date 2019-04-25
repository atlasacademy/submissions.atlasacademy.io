<?php namespace Submission\Sheet;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Submission\DropRepository;

class SubmissionV4Adapter implements AdapterInterface
{

    /**
     * @var DropRepository
     */
    private $dropRepository;
    /**
     * @var SheetClient
     */
    private $sheetClient;
    private $cache = [];
    private $sheetId;

    public function __construct(DropRepository $dropRepository,
                                SheetClient $sheetClient)
    {
        $this->dropRepository = $dropRepository;
        $this->sheetClient = $sheetClient;
    }

    public function setSheetId(string $sheetId)
    {
        $this->cache = [];
        $this->sheetId = $sheetId;

        $this->cache["Node Names"] = $this->sheetClient->getCellsRaw($sheetId, "Node Names!A:K");

        $nodes = $this->getNodesRaw();
        $nodeSheets = Collection::make($nodes)
            ->pluck("sheet_name")
            ->unique()
            ->sort();

        foreach ($nodeSheets as $sheetName) {
            $this->cache[$sheetName] = $this->sheetClient->getCellsRaw($sheetId, "{$sheetName}!A:DH");
        }
    }

    public function getNodes($regex)
    {
        $nodes = $this->getNodesRaw();
        if ($regex)
            $nodes = Collection::make($nodes)
                ->filter(function ($node) use ($regex) {
                    return preg_match($regex, $node["uid"]);
                })
                ->toArray();

        return Collection::make($nodes)
            ->pluck("uid")
            ->map(function ($uid) {
                return $this->getNode($uid);
            });
    }

    public function getNode(string $uid)
    {
        $nodes = $this->getNodesRaw();
        $node = Collection::make($nodes)
            ->filter(function ($node) use ($uid) {
                return ((string)$node["uid"]) === $uid;
            })
            ->first();

        if (!$node)
            return null;

        $sheet = $this->cache[$node["sheet_name"]];
        $runsId = $uid . "RUNS";
        $submittersId = $uid . "SBMT";
        $runsRow = null;
        $submittersRow = null;

        for ($i = 0; $i < count($sheet); $i++) {
            $row = $sheet[$i];

            if (!count($row))
                continue;

            if ($row[0] === $runsId) {
                $runsRow = $i;
            } else if ($row[0] === $submittersId) {
                $submittersRow = $i;
                break;
            }
        }

        if (!$runsRow || !$submittersRow)
            return null;

        $row = Collection::make($sheet[$runsRow]);
        $node["runs_row"] = $runsRow + 1;
        $node["submissions"] = $row->get(9, 0);

        $row = Collection::make($sheet[$submittersRow]);
        $node["submitters_row"] = $submittersRow + 1;
        $node["submitters"] = $row->get(9, 0);

        $node["drops"] = [];
        for ($i = $runsRow + 1; $i < $submittersRow; $i++) {
            $row = Collection::make($sheet[$i]);
            $dropUid = $row->get(2, null);
            if (!$dropUid)
                continue;

            $node["drops"][] = [
                "uid" => strtoupper($dropUid),
                "quantity" => $row->get(8) ? $row->get(8) : 1,
                "rate" => $row->get(5) !== null ? round($row->get(5, 0), 4) : null,
                "apd" => $row->get(6) !== null ? round($row->get(6, 0), 4) : null,
                "count" => intval($row->get(9, null)),
                "submissions" => $row->get(4),
                "row" => $i + 1
            ];
        }

        return $node;
    }

    public function addSubmission(string $nodeUid, $submitter, array $drops)
    {
        $node = $this->getNode($nodeUid);
        if ($submitter === null)
            $submitter = "anon";

        // Map the submission rows to the drop array passed by user
        $submissionMapping = [];
        foreach ($node["drops"] as $sheetDrop) {
            $dropIndex = $this->findMatchingDrop($drops, $sheetDrop["uid"], $sheetDrop["quantity"]);

            $submissionMapping[$sheetDrop["row"] - $node["runs_row"]] = $dropIndex;
        }

        // Grab all submissions for node
        $range = $node["sheet_name"] . "!L" . $node["runs_row"] . ":" . $node["submitters_row"];
        $submissions = $this->sheetClient->getCellsRaw($this->sheetId, $range);

        // Get all column numbers which match submitter's name
        $columns = $this->getSubmitterColumns($submissions, $submitter);

        // Check each column to see if submission column matches which drops weren't ignored
        $submissionColumn = null;
        foreach ($columns as $column) {
            if ($this->submissionMatchesTrackedDrops($submissions, $drops, $column, $submissionMapping)) {
                $submissionColumn = $column;
                break;
            }
        }

        // If no submission column matches, find first empty column
        if ($submissionColumn === null)
            $submissionColumn = $this->getFirstEmptySubmission($submissions);

        // Build values for update
        $values = $this->buildSubmissionValues($submissions, $node, $submitter, $drops, $submissionColumn, $submissionMapping);

        // Update sheet
        $this->sheetClient->updateCells($this->sheetId, $range, $values);

        return true;
    }

    private function getNodesRaw()
    {
        return Collection::make($this->cache["Node Names"])
            ->slice(1)
            ->filter(function ($row) {
                return count($row) >= 9
                    && $row[0]
                    && $row[1]
                    && $row[7]
                    && $row[8];
            })
            ->map(function ($row) {
                $name = trim($row[1]);
                if ($row[3])
                    $name .= " (" . trim($row[3]) . ")";

                return [
                    "uid" => strtoupper($row[0]),
                    "name" => $name,
                    "ap" => $row[4],
                    "sheet_name" => $row[7]
                ];
            })
            ->toArray();
    }

    private function buildSubmissionValues(array $submissions,
                                           array $node,
                                           $submitter,
                                           array $drops,
                                           int $column,
                                           array $submissionMapping)
    {
        $extractedColumn = $this->extractSubmissionColumn($submissions, $column);
        $data = [];

        // Set runs
        $data[0] = intval(Arr::get($extractedColumn, 0, 0)) + 1;

        // Set drops
        foreach ($submissionMapping as $k => $mapping) {
            if ($mapping === null) {
                $data[$k] = null;
                continue;
            }

            $mappedDrop = $drops[$mapping];
            if (array_key_exists("ignored", $mappedDrop) && $mappedDrop["ignored"]) {
                $data[$k] = null;
                continue;
            }

            // Check drop setting for bonus type
            $dropSetting = $this->dropRepository->getDrop($mappedDrop["uid"]);
            $isBonus = $dropSetting && $dropSetting["type"] === "Bonus Rate-Up";

            if ($isBonus) {
                $data[$k] = $mappedDrop["count"];
            } else {
                $originalValue = intval(Arr::get($extractedColumn, $k, 0));
                $resultingValue = $originalValue + $mappedDrop["count"];

                $data[$k] = $resultingValue;
            }
        }

        // Set submitter
        $submitterOffset = $node["submitters_row"] - $node["runs_row"];
        $data[$submitterOffset] = $submitter;

        // Fill empty values
        for ($i = 0; $i <= $submitterOffset; $i++) {
            if (!array_key_exists($i, $data))
                $data[$i] = null;
        }

        // Sort array by key values
        ksort($data);

        // Pad out the previous columns
        $paddedData = array_map(function ($value) use ($column) {
            $paddedRow = array_fill(0, $column, null);
            $paddedRow[$column] = $value;

            return $paddedRow;
        }, $data);

        return $paddedData;
    }

    private function extractSubmissionColumn(array $submissions, int $column): array
    {
        $extractedColumn = array_map(function ($row) use ($column) {
            return Arr::get($row, $column) === "" ? null : Arr::get($row, $column);
        }, $submissions);
        return $extractedColumn;
    }

    private function findMatchingDrop(array $drops, $uid, $quantity)
    {
        foreach ($drops as $k => $drop) {
            if ($drop["uid"] == $uid && $drop["quantity"] == $quantity)
                return $k;
        }

        return null;
    }

    private function getFirstEmptySubmission(array $submissions)
    {
        $i = 0;
        while (true) {
            $column = $this->extractSubmissionColumn($submissions, $i);

            if ($this->isColumnEmpty($column))
                return $i;

            $i++;
        }
    }

    private function getSubmitterColumns(array $submissions, $submitter)
    {
        if (!count($submissions))
            return [];

        $columns = [];
        $lastRow = $submissions[count($submissions) - 1];

        foreach ((array)$lastRow as $k => $value) {
            if ($value == $submitter)
                $columns[] = $k;
        }

        return $columns;
    }

    private function isColumnEmpty(array $column)
    {
        foreach ($column as $value) {
            if ($value !== null)
                return false;
        }

        return true;
    }

    private function submissionMatchesTrackedDrops(array $submissions, array $drops, int $column, array $submissionMapping)
    {
        // Extract column to test from submissions. Null for empty cells
        $extractedColumn = $this->extractSubmissionColumn($submissions, $column);

        foreach ($extractedColumn as $row => $value) {
            // If row does not have a drop mapping, it could be one of the following scenarios:
            // - RUNS row
            // - SBMT row
            // - A blank row for formatting (ie. event currency divider)
            // - A new drop which wasn't tracked before
            //
            // In any of the cases above, submission will be allowed to match to column
            if (!array_key_exists($row, $submissionMapping) || $submissionMapping[$row] === null)
                continue;

            // Get drop value that user passed
            $dropKey = $submissionMapping[$row];
            $mappedDrop = $drops[$dropKey];

            // If ignored, but value is filled in, submission does not match
            $isIgnored = array_key_exists("ignored", $mappedDrop) && $mappedDrop["ignored"];
            if ($isIgnored && $value !== null)
                return false;

            // If not ignored, but value is omitted, submission does not match
            if (!$isIgnored && $value === null)
                return false;

            // Get drop setting in order to check drop type
            $uid = $mappedDrop["uid"];
            $count = $mappedDrop["count"];
            $dropSetting = $this->dropRepository->getDrop($uid);

            // If not Bonus Rate-Up, skip
            if (!$dropSetting || $dropSetting["type"] !== "Bonus Rate-Up")
                continue;

            // Bonus Rate-Ups are handled differently, they don't append but rather group all submissions
            // of the same type together. If the bonus doesn't match the value passed, column doesn't match.
            if ($value !== $count)
                return false;
        }

        // If all the rows passed successfully, submission matches column
        return true;
    }
}
