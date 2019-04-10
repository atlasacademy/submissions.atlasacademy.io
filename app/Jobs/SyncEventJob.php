<?php namespace App\Jobs;

use Illuminate\Support\Arr;
use Submission\EventNodeRepository;
use Submission\EventRepository;
use Submission\SheetManager;

class SyncEventJob extends Job
{

    /**
     * @var string
     */
    private $eventUid;

    public function __construct(string $eventUid)
    {
        $this->eventUid = $eventUid;
    }

    public function handle(EventRepository $eventRepository,
                           EventNodeRepository $eventNodeRepository,
                           SheetManager $sheetManager)
    {
        $event = $eventRepository->getEvent($this->eventUid);
        if (!$event)
            throw new \Exception("Invalid event uid");

        $sheetManager->setSheetType($event["sheet_type"]);
        $sheetManager->setSheetId($event["sheet_id"]);

        $newNodes = $sheetManager->getNodes($event["node_filter"]);

        $this->syncNodes($eventNodeRepository, $newNodes);
    }

    private function syncNodes(EventNodeRepository $eventNodeRepository, $newNodes)
    {
        $currentNodes = $eventNodeRepository->getNodes($this->eventUid);

        $newNodeUids = Arr::pluck($newNodes, "uid");
        foreach ($newNodes as $k => $newNode) {
            $newNode["sort"] = $k;
            $newNode["active"] = true;
            $currentNode = $eventNodeRepository->getNode($this->eventUid, $newNode["uid"]);

            if ($currentNode)
                $eventNodeRepository->update($this->eventUid, $newNode["uid"], $newNode);
            else
                $eventNodeRepository->create($this->eventUid, $newNode);
        }

        foreach ($currentNodes as $currentNode) {
            if (in_array($currentNode["uid"], $newNodeUids))
                continue;

            $eventNodeRepository->setActive($this->eventUid, $currentNode["uid"], false);
        }
    }

}
