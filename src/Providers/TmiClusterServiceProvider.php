<?php

namespace GhostZero\TmiCluster\Providers;

use Exception;
use GhostZero\TmiCluster\Commands;
use GhostZero\TmiCluster\Contracts;
use GhostZero\TmiCluster\EventMap;
use GhostZero\TmiCluster\ServiceBindings;
use GhostZero\TmiCluster\TmiCluster;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class TmiClusterServiceProvider extends ServiceProvider
{
    use EventMap, ServiceBindings;

    /**
     * Boot the TmiCluster service.
     *
     * @throws Exception
     */
    public function boot(): void
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerEvents();
        $this->registerServices();
        $this->registerCommands();
        $this->registerFrontend();
        $this->defineAssetPublishing();
        $this->registerLogger();
    }

    protected function registerEvents(): void
    {
        $events = $this->app->make(Dispatcher::class);

        foreach ($this->events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
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

        $this->app->singleton(Contracts\ChannelManager::class, config('tmi-cluster.channel_manager.use'));
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
                Commands\TmiClusterInstallCommand::class,
                Commands\TmiClusterPublishCommand::class,
                Commands\TmiClusterTerminateCommand::class,
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

    private function registerLogger(): void
    {
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
