<?php

namespace App\Console;

use App\Console\Commands\AddEventCommand;
use App\Console\Commands\SyncDropsCommand;
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
        SyncDropsCommand::class,
        UpdateEventCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command("submissions:sync_drops")->everyThirtyMinutes();
        $schedule->job(SyncActiveEventsJob::class)->everyThirtyMinutes();
    }
}
