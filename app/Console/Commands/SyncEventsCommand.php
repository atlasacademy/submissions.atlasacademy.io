<?php namespace App\Console\Commands;

use App\Jobs\SyncEventJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Submission\EventRepository;

class SyncEventsCommand extends Command
{

    protected $name = "submissions:sync_events";
    protected $description = "Syncs all active events.";
    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var EventRepository
     */
    private $eventRepository;

    public function __construct(Dispatcher $dispatcher,
                                EventRepository $eventRepository)
    {
        parent::__construct();

        $this->dispatcher = $dispatcher;
        $this->eventRepository = $eventRepository;
    }

    public function handle()
    {
        $this->output->section("Syncing events ...");
        $events = $this->eventRepository->getEvents();

        foreach ($events as $event) {
            $this->output->text("Syncing event: {$event["name"]}");
            $job = new SyncEventJob($event["uid"]);
            $this->dispatcher->dispatchNow($job);
        }
    }

}