<?php

namespace GhostZero\TmiCluster\Tests;


use GhostZero\TmiCluster\Facades\TmiCluster;
use GhostZero\TmiCluster\Providers\TmiClusterServiceProvider;
use Illuminate\Foundation\Application;

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
}
