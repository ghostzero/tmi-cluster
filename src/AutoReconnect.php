<?php

namespace GhostZero\TmiCluster;

use Exception;
use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\ChannelManager;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Repositories\DatabaseChannelManager;
use GhostZero\TmiCluster\Traits\Lockable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Throwable;

/**
 * The auto-reconnect will try to reconnect the channels that are not connected. This happens every 30 seconds.
 *
 * This feature is currently only supported by the database channel manager.
 */
class AutoReconnect
{
    use Lockable;

    private float $counter;

    public function __construct()
    {
        try {
            $this->counter = random_int(0, 30);
        } catch (Exception $e) {
            $this->counter = 0;
        }
    }

    public function handle(Supervisor $supervisor): void
    {
        try {
            $this->counter++;

            $channelManager = app(ChannelManager::class);

            if ($channelManager instanceof DatabaseChannelManager) {
                $this->doReconnect($supervisor, $channelManager);
            }
        } catch (Throwable $exception) {
            $supervisor->output(null, $exception->getMessage());
            $supervisor->output(null, $exception->getTraceAsString());
        }
    }

    private function doReconnect(Supervisor $supervisor, DatabaseChannelManager $channelManager)
    {
        if ($this->counter % 30 !== 0) return;
        $lock = $this->lock('reconnect-disconnected-channels', 10);

        try {
            $lock->block(5);
            // Lock acquired after waiting maximum of 5 seconds...
            $connectedChannels = SupervisorProcess::all()
                ->filter(fn(SupervisorProcess $process) => !$process->is_stale)
                ->map(fn(SupervisorProcess $process) => $process->channels)
                ->collapse()
                ->toArray();

            $disconnectedChannels = $channelManager->disconnected($connectedChannels);

            $count = count($disconnectedChannels);

            if ($count > 0) {
                $supervisor->output(null, sprintf('Reconnecting %d disconnected channels...', $count));
                app(ChannelDistributor::class)->join($disconnectedChannels);
            }
        } catch (LockTimeoutException $e) {
            $supervisor->output(null, 'Unable to acquire reconnect lock...');
        } finally {
            optional($lock)->release();
        }
    }


}