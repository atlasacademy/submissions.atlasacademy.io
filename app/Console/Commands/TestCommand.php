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
        $this->dispatcher->dispatchNow(new ExportSubmissionJob("d8925e42-6585-11e9-a10b-0242ac110006"));
    }

}
