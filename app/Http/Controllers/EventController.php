<?php namespace App\Http\Controllers;

use Laravel\Lumen\Http\ResponseFactory;
use Submission\EventNodeRepository;
use Submission\EventRepository;

class EventController extends Controller
{

    /**
     * @var EventRepository
     */
    private $eventRepository;
    /**
     * @var EventNodeRepository
     */
    private $eventNodeRepository;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(EventRepository $eventRepository,
                                EventNodeRepository $eventNodeRepository,
                                ResponseFactory $responseFactory)
    {
        $this->eventRepository = $eventRepository;
        $this->eventNodeRepository = $eventNodeRepository;
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
        if (!$event)
            abort(404);

        $event["nodes"] = $this->eventNodeRepository->getNodes($uid);

        return $this->responseFactory->json($event);
    }

}
