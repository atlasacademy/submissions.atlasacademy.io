<?php namespace App\Console\Commands;

use App\Jobs\SyncEventJob;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Submission\EventRepository;

class AddEventCommand extends Command
{

    protected $name = "submissions:add_event";
    protected $description = "Creates a new event and populates data.";

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
        $sheet_type = $this->choice('Set sheet type', ['submissionsV4'], 'submissionsV4');
        $uid = $this->ask('Input event uid');
        $sheet_id = $this->ask('Input sheet id');
        $name = $this->ask('Input event name');
        $node_filter = $this->ask('Input node filter', null);
        $submittable = $this->choice('Is event submittable?', ['yes', 'no'], 'yes');
        $position = $this->choice('Add event in what position?', ['first', 'last'], 'first');

        $this->output->text("Creating event ...");
        $event = $this->eventRepository->create(compact(
            "uid",
            "sheet_type",
            "sheet_id",
            "name",
            "node_filter"
        ));

        $this->output->text("Syncing event settings ...");
        $this->dispatcher->dispatchNow(new SyncEventJob($event["uid"]));

        $this->output->text("Activating event ...");
        $this->eventRepository->setActive($event["uid"], true);
        $this->eventRepository->reorderEvents($event["uid"], $position === "first");
        if ($submittable === "yes")
            $this->eventRepository->setSubmittable($event["uid"], true);
    }

}
