<?php namespace App\Jobs;

use Illuminate\Support\Arr;
use Submission\EventNodeDropRepository;
use Submission\EventNodeRepository;
use Submission\EventRepository;
use Submission\SheetManager;

class SyncEventJob extends Job
{

    /**
     * @var string
     */
    private $eventUid;
    /**
     * @var EventRepository
     */
    private $eventRepository;
    /**
     * @var EventNodeRepository
     */
    private $eventNodeRepository;
    /**
     * @var EventNodeDropRepository
     */
    private $eventNodeDropRepository;
    /**
     * @var SheetManager
     */
    private $sheetManager;

    public function __construct(string $eventUid)
    {
        $this->eventUid = $eventUid;
    }

    public function handle(EventRepository $eventRepository,
                           EventNodeRepository $eventNodeRepository,
                           EventNodeDropRepository $eventNodeDropRepository,
                           SheetManager $sheetManager)
    {
        $this->eventRepository = $eventRepository;
        $this->eventNodeRepository = $eventNodeRepository;
        $this->eventNodeDropRepository = $eventNodeDropRepository;
        $this->sheetManager = $sheetManager;

        $event = $this->eventRepository->getEvent($this->eventUid);
        if (!$event)
            throw new \Exception("Invalid event uid");

        $sheetManager->setSheetType($event["sheet_type"]);
        $sheetManager->setSheetId($event["sheet_id"]);

        $newNodes = $sheetManager->getNodes($event["node_filter"]);

        $this->syncNodes($newNodes);
    }

    private function syncNodes($newNodes)
    {
        $currentNodes = $this->eventNodeRepository->getNodes($this->eventUid);

        $newNodeUids = Arr::pluck($newNodes, "uid");
        foreach ($newNodes as $k => $newNode) {
            $newNode["sort"] = $k;
            $newNode["active"] = true;
            $currentNode = $this->eventNodeRepository->getNode($this->eventUid, $newNode["uid"]);

            if ($currentNode)
                $this->eventNodeRepository->update($this->eventUid, $newNode["uid"], $newNode);
            else
                $this->eventNodeRepository->create($this->eventUid, $newNode);

            $this->syncNodeDrops($newNode);
        }

        foreach ($currentNodes as $currentNode) {
            if (in_array($currentNode["uid"], $newNodeUids))
                continue;

            $this->eventNodeRepository->setActive($this->eventUid, $currentNode["uid"], false);
        }
    }

    private function syncNodeDrops($newNode)
    {
        $currentDrops = $this->eventNodeDropRepository->getDrops($this->eventUid, $newNode["uid"]);
        $newDrops = $newNode["drops"];
        $newDropUids = array_map(function ($newDrop) {
            return $newDrop["uid"] . "_" . $newDrop["quantity"];
        }, $newDrops);

        foreach ($newDrops as $k => $newDrop) {
            $newDrop["sort"] = $k;

            $currentDrop = $this->eventNodeDropRepository->getDrop(
                $this->eventUid,
                $newNode["uid"],
                $newDrop["uid"],
                $newDrop["quantity"]
            );

            if ($currentDrop) {
                $this->eventNodeDropRepository->update(
                    $this->eventUid,
                    $newNode["uid"],
                    $newDrop["uid"],
                    $newDrop["quantity"],
                    $newDrop
                );
            } else {
                $this->eventNodeDropRepository->create(
                    $this->eventUid,
                    $newNode["uid"],
                    $newDrop
                );
            }
        }

        foreach ($currentDrops as $currentDrop) {
            if (in_array($currentDrop["uid"] . "_" . $currentDrop["quantity"], $newDropUids))
                continue;

            $this->eventNodeDropRepository->delete(
                $this->eventUid,
                $newNode["uid"],
                $currentDrop["uid"],
                $currentDrop["quantity"]
            );
        }
    }

}
