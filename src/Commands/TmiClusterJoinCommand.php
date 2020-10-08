<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use Illuminate\Console\Command;

class TmiClusterJoinCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster:join {channel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Join a channel in one irc process instance.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        /** @var CommandQueue $commandQueue */
        $commandQueue = app(CommandQueue::class);

        $commandQueue->push('*', 'tmi:join', [
            'channel' => $this->argument('channel')
        ]);

        return 0;
    }
}
