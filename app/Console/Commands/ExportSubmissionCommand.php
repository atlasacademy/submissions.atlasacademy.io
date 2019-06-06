<?php namespace App\Console\Commands;

use App\Jobs\ExportSubmissionJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Submission\SubmissionRepository;

class ExportSubmissionCommand extends Command
{


    protected $name = "submissions:export_submission";
    protected $description = "Manually calls export submission job.";

    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var SubmissionRepository
     */
    private $submissionRepository;

    public function __construct(Dispatcher $dispatcher,
                                SubmissionRepository $submissionRepository)
    {
        parent::__construct();

        $this->dispatcher = $dispatcher;
        $this->submissionRepository = $submissionRepository;
    }

    public function handle()
    {
        $receipt = $this->ask('Input submission receipt');

        $submission = $this->submissionRepository->getSubmission($receipt);
        if (!$submission) {
            $this->output->text("Submission not found.");
            return;
        }

        $this->submissionRepository->setUploaded($receipt, false);

        $this->output->text("Dispatching export job ...");
        $this->dispatcher->dispatch(new ExportSubmissionJob($receipt));
    }

}