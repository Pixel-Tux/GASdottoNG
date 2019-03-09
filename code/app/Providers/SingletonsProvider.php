<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Singletons\OrderNumbersDispatcher;
use App\Singletons\MovementsHub;
use App\Singletons\LogHarvester;

class SingletonsProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        $this->app->singleton('OrderNumbersDispatcher', function ($app) {
            return new OrderNumbersDispatcher();
        });

        $this->app->singleton('MovementsHub', function ($app) {
            return new MovementsHub();
        });

        $this->app->singleton('LogHarvester', function ($app) {
            return new LogHarvester();
        });
    }
}
