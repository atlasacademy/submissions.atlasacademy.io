<?php namespace App\Jobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Submission\EventRepository;

class SyncActiveEventsJob extends Job
{

    public function handle(Dispatcher $dispatcher,
                           EventRepository $eventRepository)
    {
        $events = $eventRepository->getEvents();

        foreach ($events as $event) {
            $job = new SyncEventJob($event["uid"]);
            $dispatcher->dispatch($job);
        }
    }

}
