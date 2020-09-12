<?php

namespace GhostZero\TmiCluster\Tests;


use GhostZero\TmiCluster\Facades\TmiCluster;
use GhostZero\TmiCluster\Providers\TmiClusterServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup

        $this->loadLaravelMigrations(['--database' => 'testbench']);
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

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
