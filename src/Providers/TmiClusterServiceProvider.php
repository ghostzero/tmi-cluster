<?php

namespace GhostZero\TmiCluster\Providers;

use GhostZero\TmiCluster\Commands;
use GhostZero\TmiCluster\Models;
use GhostZero\TmiCluster\ServiceBindings;
use GhostZero\TmiCluster\Supervisor;
use GhostZero\TmiCluster\TmiCluster;
use Illuminate\Support\ServiceProvider;

class TmiClusterServiceProvider extends ServiceProvider
{
    use ServiceBindings;

    public function boot()
    {
        $this->configure();
        $this->registerServices();
        $this->registerCommands();
    }

    /**
     * Setup the configuration for TmiCluster.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/tmi-cluster.php', 'tmi-cluster');
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');

        $this->publishes([
            __DIR__ . '/../../config/tmi-cluster.php' => config_path('tmi-cluster.php'),
        ]);

        TmiCluster::use(config('tmi-cluster.use'));
    }

    /**
     * Register TmiCluster's services in the container.
     *
     * @return void
     */
    protected function registerServices()
    {
        foreach ($this->serviceBindings as $key => $value) {
            is_numeric($key)
                ? $this->app->singleton($value)
                : $this->app->singleton($key, $value);
        }
    }

    /**
     * Register the TmiCluster Artisan commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TmiClusterCommand::class,
                Commands\TmiClusterProcessCommand::class,
            ]);
        }
    }
}
