<?php namespace Submission\Sheet;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SubmissionV4Adapter implements AdapterInterface
{

    /**
     * @var SheetClient
     */
    private $sheetClient;
    private $cache = [];

    public function __construct(SheetClient $sheetClient)
    {
        $this->sheetClient = $sheetClient;
    }

    public function setSheetId(string $sheetId)
    {
        $this->cache = [];

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
                return $node["uid"] === $uid;
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
        $node["runs_row"] = $runsRow;
        $node["submissions"] = $row->get(9, 0);

        $row = Collection::make($sheet[$submittersRow]);
        $node["submitters_row"] = $submittersRow;
        $node["submitters"] = $row->get(9, 0);

        return $node;
    }

    public function addSubmission(string $nodeUid, int $runs, $submitter, array $drops)
    {
        // TODO: Implement addSubmission() method.
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
                    "uid" => $row[0],
                    "name" => $name,
                    "ap" => $row[4],
                    "sheet_name" => $row[7]
                ];
            })
            ->toArray();
    }
}
