<?php namespace App\Console\Commands;

use App\Jobs\SyncEventJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Submission\EventRepository;

class UpdateEventCommand extends Command
{

    protected $name = "submissions:update_event";
    protected $description = "Update event and sync settings.";
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
        $uid = $this->ask('Input event uid');
        $event = $this->eventRepository->getEvent($uid);
        if (!$event) {
            $this->output->text("Event not found.");
            return;
        }

        $name = $this->ask('Input event name', $event["name"]);
        $submittable = $this->choice('Is event submittable?', ['yes', 'no'], 'yes');
        $position = $this->choice('Move event to what position?', ['first', 'last'], 'first');

        $event = $this->eventRepository->getEvent($uid);
        if (!$event) {
            $this->output->text("Event not found.");
            return;
        }

        $this->output->text("Updating event ...");
        $event = $this->eventRepository->update($uid, compact("name"));

        $this->output->text("Syncing event settings ...");
        $this->dispatcher->dispatchNow(new SyncEventJob($event["uid"]));

        $this->output->text("Activating event ...");
        $this->eventRepository->setActive($event["uid"], true);
        $this->eventRepository->reorderEvents($event["uid"], $position === "first");
        if ($submittable === "yes")
            $this->eventRepository->setSubmittable($event["uid"], true);
    }

}
