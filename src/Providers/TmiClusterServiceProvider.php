<?php

namespace GhostZero\TmiCluster\Providers;

use Exception;
use GhostZero\TmiCluster\Commands;
use GhostZero\TmiCluster\ServiceBindings;
use GhostZero\TmiCluster\TmiCluster;
use Illuminate\Support\ServiceProvider;

class TmiClusterServiceProvider extends ServiceProvider
{
    use ServiceBindings;

    /**
     * Boot the TmiCluster service.
     *
     * @throws Exception
     */
    public function boot(): void
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerServices();
        $this->registerCommands();
        $this->registerFrontend();
        $this->defineAssetPublishing();
    }

    /**
     * Setup the configuration for TmiCluster.
     *
     * @return void
     * @throws Exception
     */
    protected function configure(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/tmi-cluster.php', 'tmi-cluster');
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');

        TmiCluster::use(config('tmi-cluster.use'));
    }

    /**
     * Register TmiCluster's services in the container.
     *
     * @return void
     */
    protected function registerServices(): void
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
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\TmiClusterCommand::class,
                Commands\TmiClusterListCommand::class,
                Commands\TmiClusterJoinCommand::class,
                Commands\TmiClusterProcessCommand::class,
                Commands\TmiClusterPurgeCommand::class,
            ]);
        }
    }

    /**
     * Register the TmiCluster frontend.
     *
     * @return void
     */
    private function registerFrontend(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'tmi-cluster');
    }

    private function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/tmi-cluster.php' => config_path('tmi-cluster.php'),
            ], 'tmi-cluster-config');
        }
    }

    private function defineAssetPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/tmi-cluster')
        ], 'tmi-cluster-assets');
    }
}
