<?php

namespace GhostZero\TmiCluster;

use InvalidArgumentException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

trait Watchable
{
    protected function startServerWatcher(): Process
    {
        if (empty($paths = config('tmi-cluster.watch'))) {
            throw new InvalidArgumentException(
                'List of directories/files to watch not found. Please update your "config/tmi-cluster.php" configuration file.',
            );
        }

        return tap(new Process([
            (new ExecutableFinder)->find('node'),
            'file-watcher.js',
            json_encode(collect(config('octane.watch'))->map(fn($path) => base_path($path))),
            $this->option('poll'),
        ], realpath(__DIR__ . '/../bin'), null, null, null))->start();
    }
}