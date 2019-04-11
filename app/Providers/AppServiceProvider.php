<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Submission\Sheet\SheetClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SheetClient::class);
    }
}
