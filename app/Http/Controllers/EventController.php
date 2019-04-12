<?php namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use Laravel\Lumen\Http\ResponseFactory;
use Submission\DropRepository;
use Submission\EventNodeDropRepository;
use Submission\EventNodeRepository;
use Submission\EventRepository;

class EventController extends Controller
{

    /**
     * @var DropRepository
     */
    private $dropRepository;
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
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(DropRepository $dropRepository,
                                EventRepository $eventRepository,
                                EventNodeRepository $eventNodeRepository,
                                EventNodeDropRepository $eventNodeDropRepository,
                                ResponseFactory $responseFactory)
    {
        $this->dropRepository = $dropRepository;
        $this->eventRepository = $eventRepository;
        $this->eventNodeRepository = $eventNodeRepository;
        $this->eventNodeDropRepository = $eventNodeDropRepository;
        $this->responseFactory = $responseFactory;
    }

    public function index()
    {
        $events = $this->eventRepository->getEvents();

        return $this->responseFactory->json($events);
    }

    public function get(string $uid)
    {
        $event = $this->eventRepository->getEvent($uid);
        if (!$event || !$event["active"])
            abort(404);

        $event["nodes"] = $this->eventNodeRepository->getNodes($uid);

        $nodeUids = Arr::pluck($event["nodes"], "uid");
        $event["node_drops"] = $this->eventNodeDropRepository->getDropsForNodes($uid, $nodeUids);

        $dropUids = Arr::pluck($event["node_drops"], "uid");
        $event["drops"] = $this->dropRepository->getDropsWithUids($dropUids);

        return $this->responseFactory->json($event);
    }

}
