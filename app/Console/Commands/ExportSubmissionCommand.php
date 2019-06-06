<?php namespace App\Console\Commands;

use App\Jobs\ExportSubmissionJob;
use Illuminate\Contracts\Bus\Dispatcher;

class ExportSubmissionCommand extends Command
{


    protected $name = "submissions:export_submission";
    protected $description = "Manually calls export submission job.";

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