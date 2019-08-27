<?php namespace App\Console\Commands;

use App\Jobs\SyncDropsJob;
use Illuminate\Support\Collection;
use Submission\DropRepository;
use Submission\Sheet\SheetClient;

class SyncDropsCommand extends Command
{

    const DROPS_SHEET_ID = "1su2GKPlUJ4uB8Vkdck4wgIwQYINMEBz4kcWSSlhDKtk";
    const EVENT_DROP_SHEET = "Event Mats Only";
    const NORMAL_DROP_SHEET = "Ascension Mats Names";

    protected $name = "submissions:sync_drops";
    protected $description = "Imports drops from sheet template.";
    /**
     * @var DropRepository
     */
    private $dropRepository;
    /**
     * @var SheetClient
     */
    private $sheetClient;

    public function __construct(DropRepository $dropRepository,
                                SheetClient $sheetClient)
    {
        parent::__construct();

        $this->dropRepository = $dropRepository;
        $this->sheetClient = $sheetClient;
    }

    public function handle()
    {
        $this->output->section("Syncing drops ...");

        $this->output->text("Fetching normal drops ...");
        $normalDrops = $this->fetchDrops(self::NORMAL_DROP_SHEET, false);

        $this->output->text("Fetching event drops ...");
        $eventDrops = $this->fetchDrops(self::EVENT_DROP_SHEET, true);

        $newDrops = $this->sortDrops($normalDrops, $eventDrops);
        $newDropUids = $newDrops->pluck("uid")->toArray();
        $currentDrops = $this->dropRepository->getDrops();

        $this->output->text("Updating drops ...");
        $bar = $this->output->createProgressBar(count($newDrops));
        $bar->start();
        foreach ($newDrops as $k => $newDrop) {
            $newDrop["sort"] = $k;
            $newDrop["active"] = true;

            $drop = $this->dropRepository->getDrop($newDrop["uid"]);
            if ($drop) {
                $this->dropRepository->update($newDrop["uid"], $newDrop, true);
            } else {
                $this->dropRepository->create($newDrop, true);
            }

            $bar->advance();
        }
        $bar->finish();
        $this->output->newLine();

        $this->output->text("Deactivating orphaned drops ...");
        $bar = $this->output->createProgressBar(count($newDrops));
        $bar->start();
        foreach ($currentDrops as $currentDrop) {
            if (in_array($currentDrop["uid"], $newDropUids))
                continue;

            $this->dropRepository->update($currentDrop["uid"], ["active" => false]);
            $this->dropRepository->removeImage($currentDrop["uid"]);

            $bar->advance();
        }
        $bar->finish();
        $this->output->newLine();
    }


    private function fetchDrops(string $sheetName, bool $event): Collection
    {
        $range = "{$sheetName}!A:G";
        $results = $this->sheetClient->getCellsRaw(self::DROPS_SHEET_ID, $range);

        return Collection::make($results)
            ->slice(1)
            ->filter(function ($row) {
                return count($row) >= 6
                    && $row[0]
                    && $row[1]
                    && $row[4];
            })
            ->map(function ($row) use ($event) {
                $uid = $row[0];
                $name = $row[1];
                $type = "Material";
                $currency = $event && preg_match('/[0-9]$/', $uid);

                if (preg_match('/\\[Bonus Rate-up\\]$/', $name))
                    $type = "Bonus Rate-Up";
                else if ($uid == "QP00" || preg_match('/^Q[0-9]+$/', $uid))
                    $type = "QP";

                return [
                    "uid" => $uid,
                    "name" => $name,
                    "type" => $type,
                    "quantity" => $row[5] ? $row[5] : null,
                    "image_original" => array_key_exists(6, $row) ? $row[6] : null,
                    "event" => $event,
                    "currency" => $currency,
                ];
            });
    }

    private function sortDrops(Collection $normalDrops, Collection $eventDrops): Collection
    {
        $drops = Collection::make();

        foreach ($eventDrops as $drop) {
            if ($drop["type"] === "CE")
                $drops->push($drop);
        }

        foreach ($normalDrops as $drop) {
            $drops->push($drop);
        }

        foreach ($eventDrops as $drop) {
            if ($drop["type"] !== "CE")
                $drops->push($drop);
        }

        return $drops;
    }
}
