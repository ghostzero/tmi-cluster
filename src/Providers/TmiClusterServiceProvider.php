<?php

namespace GhostZero\TmiCluster\Providers;

use GhostZero\TmiCluster\Commands;
use Illuminate\Support\ServiceProvider;

class TmiClusterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/tmi-cluster.php', 'tmi-cluster');
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../../factories');

        $this->publishes([
            __DIR__ . '/../../config/tmi-cluster.php' => config_path('tmi-cluster.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TmiCommand::class,
                Commands\TmiWorkCommand::class,
            ]);
        }
    }
}
