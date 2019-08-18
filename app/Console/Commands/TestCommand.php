<?php namespace App\Console\Commands;

use App\Jobs\ExportSubmissionJob;
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
        $this->dispatcher->dispatchNow(new RevertSubmissionJob("fe2f7c4c-c18e-11e9-8236-0242ac120005"));
    }

}
