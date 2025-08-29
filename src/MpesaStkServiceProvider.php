<?php

namespace GrishonMunene\MpesaStk;

use Illuminate\Support\ServiceProvider;
use GrishonMunene\MpesaStk\Services\MpesaService;

class MpesaStkServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/mpesa.php', 'mpesa');

        $this->app->singleton(MpesaService::class, function ($app) {
            return new MpesaService();
        });
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__.'/config/mpesa.php' => config_path('mpesa.php'),
        ], 'mpesa-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/Database/migrations/' => database_path('migrations'),
        ], 'mpesa-migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
    }
}
