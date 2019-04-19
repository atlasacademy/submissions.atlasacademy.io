<?php namespace App\Console\Commands;

use App\Jobs\ExportSubmissionJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Submission\Sheet\SheetClient;

class TestCommand extends Command
{

    protected $name = "submissions:test";
    /**
     * @var SheetClient
     */
    private $sheetClient;
    /**
     * @var Dispatcher
     */
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher,
                                SheetClient $sheetClient)
    {
        parent::__construct();

        $this->sheetClient = $sheetClient;
        $this->dispatcher = $dispatcher;
    }

    public function handle()
    {
        $this->dispatcher->dispatchNow(new ExportSubmissionJob("6744dc76-6277-11e9-a772-0242ac110006"));
    }

}