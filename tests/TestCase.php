<?php

namespace GhostZero\TmiCluster\Tests;


use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Facades\TmiCluster;
use GhostZero\TmiCluster\Providers\TmiClusterServiceProvider;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Foundation\Application;
use Predis\ClientInterface;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();
        // additional setup

        //$this->artisan('migrate')->run();
        $this->flushRedis();
    }

    protected function getPackageProviders($app)
    {
        return [
            TmiClusterServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'TmiCluster' => TmiCluster::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function flushRedis()
    {
        /** @var ClientInterface $connection */
        $connection = app(Factory::class)->connection('tmi-cluster');
        $connection->flushdb();
    }

    protected function useChannelManager(string $concrete): ChannelManager
    {
        $this->app->singleton(ChannelManager::class, $concrete);

        return app(ChannelManager::class);
    }
}
