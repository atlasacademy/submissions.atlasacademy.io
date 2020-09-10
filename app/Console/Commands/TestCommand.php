<?php namespace App\Console\Commands;

use App\Jobs\ExportSubmissionJob;
use App\Jobs\ParseSubmissionJob;
use App\Jobs\RevertSubmissionJob;
use App\Jobs\SyncEventJob;
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
        $this->dispatcher->dispatchNow(new ParseSubmissionJob("84883404-f321-11ea-878c-0242ac140004"));
    }

}
