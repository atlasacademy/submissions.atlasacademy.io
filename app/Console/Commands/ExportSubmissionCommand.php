<?php namespace App\Console\Commands;

use App\Jobs\ExportSubmissionJob;
use Illuminate\Contracts\Bus\Dispatcher;

class ExportSubmissionCommand extends Command
{


    protected $name = "submissions:add_event";
    protected $description = "Creates a new event and populates data.";

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        parent::__construct();

        $this->dispatcher = $dispatcher;
    }

    public function handle()
    {
        $receipt = $this->ask('Input submission receipt');

        $this->output->text("Dispatching export job ...");
        $this->dispatcher->dispatch(new ExportSubmissionJob($receipt));
    }

}