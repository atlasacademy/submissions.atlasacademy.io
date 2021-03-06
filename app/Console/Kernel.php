<?php

namespace App\Console;

use App\Console\Commands\AddEventCommand;
use App\Console\Commands\ExportSubmissionCommand;
use App\Console\Commands\GenerateTokenCommand;
use App\Console\Commands\SyncDropsCommand;
use App\Console\Commands\SyncEventsCommand;
use App\Console\Commands\TestCommand;
use App\Console\Commands\UpdateEventCommand;
use App\Jobs\SyncActiveEventsJob;
use App\Jobs\SyncDropsJob;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        AddEventCommand::class,
        ExportSubmissionCommand::class,
        GenerateTokenCommand::class,
        SyncDropsCommand::class,
        SyncEventsCommand::class,
        UpdateEventCommand::class,
        TestCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command("submissions:sync_drops")->hourly();
        $schedule->job(new SyncActiveEventsJob())->hourly();
    }
}
