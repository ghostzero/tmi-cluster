<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Cache\RedisLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Collection;
use Predis\ClientInterface;
use Throwable;

/**
 * Scale out by one instance if average channel usage is above 70%,
 * and scale in by one instance if channel usage falls below 50%.
 *
 * Avoid flapping where scale-in and scale-out actions continually
 * go back and forth. Suppose there are two instances, and upper
 * limit is 80% channels, lower limit is 60%. When the load is at 85%,
 * another instance is added. After some time, the load decreases
 * to 60%. Before scaling in, the autoscale service calculates the
 * distribution of total load (of three instances) when an instance
 * is removed, taking it to 90%. This means it would have to scale
 * out again immediately. So, it skips scaling-in and you might never
 * see the expected scaling results.
 *
 * The flapping situation can be controlled by choosing an adequate
 * margin between the scale-out and scale-in thresholds.
 */
class AutoScale
{
    private float $counter;

    public function __construct()
    {
        $this->counter = 0;
    }

    public function scale(Supervisor $supervisor): void
    {
        try {
            $this->counter++;

            $channels = $this->getCurrentChannels($supervisor);
            $averageUsage = $this->getCurrentAverageChannelUsage($channels);
            $nextUsage = $this->getNextAverageChannelUsage($channels);

            if ($this->shouldScaleOut($averageUsage)) {
                $this->scaleOut($supervisor);
            } elseif ($this->shouldScaleIn($averageUsage)) {
                if (!$this->shouldScaleOut($nextUsage)) {
                    $this->scaleIn($supervisor);
                }
            }

            $this->setMinimumScale($supervisor->processes()->count());

            $this->releaseStaleSupervisors($supervisor);
        } catch (Throwable $exception) {
            $supervisor->output(null, $exception->getTraceAsString());
        }
    }

    /**
     * Get the Redis connection instance.
     *
     * @return Connection|ClientInterface
     */
    private function connection()
    {
        return app(Factory::class)->connection('tmi-cluster');
    }

    private function lock(string $name, int $seconds = 0, ?string $owner = null): RedisLock
    {
        return new RedisLock($this->connection(), $name, $seconds, $owner);
    }

    private function releaseStaleSupervisors(Supervisor $supervisor): void
    {
        if ($this->counter % 10 !== 0) return;
        $lock = $this->lock('release-stale-supervisors', 10);

        try {
            $lock->block(5);
            // Lock acquired after waiting maximum of 5 seconds...
            app(SupervisorRepository::class)->flushStale();
        } catch (LockTimeoutException $e) {
            $supervisor->output(null, 'Unable to acquire lock...');
        } finally {
            optional($lock)->release();
        }
    }

    public function shouldScaleOut(float $usage): bool
    {
        return $usage > config('tmi-cluster.auto_scale.thresholds.scale_out', 70);
    }

    public function shouldRestoreScale(): bool
    {
        return config('tmi-cluster.auto_scale.restore', true);
    }

    public function shouldScaleIn(float $usage): bool
    {
        return $usage < config('tmi-cluster.auto_scale.thresholds.scale_in', 50);
    }

    private function getCurrentAverageChannelUsage(Collection $c)
    {
        $channelLimit = config('tmi-cluster.auto_scale.thresholds.channels', 50);
        $channelCount = $c->sum();
        $serverCount = $c->count() + 1;

        return (($channelCount / $serverCount) / $channelLimit) * 100;
    }

    private function getNextAverageChannelUsage(Collection $c)
    {
        $channelLimit = config('tmi-cluster.auto_scale.thresholds.channels', 50);
        $channelCount = $c->sum();
        $serverCount = $c->count();

        if ($serverCount <= 0) {
            return 0;
        }

        return (($channelCount / $serverCount) / $channelLimit) * 100;
    }

    public function scaleOut(Supervisor $supervisor): void
    {
        $count = $supervisor->processes()->count();
        $supervisor->output(null, 'Scale out: ' . ($count + 1));

        if ($count >= config('tmi-cluster.auto_scale.processes.max', 25)) {
            return; // skip scale out, keep a maximum of instance
        }

        $supervisor->scale($count + 1);
    }

    public function scaleIn(Supervisor $supervisor): void
    {
        $count = $supervisor->processes()->count();

        if ($count <= $this->getMinimumScale()) {
            return; // skip scale in, keep a minimum of instance
        }

        $supervisor->output(null, 'Scale in: ' . ($count - 1));
        $supervisor->scale($count - 1);
    }

    private function getCurrentChannels(Supervisor $supervisor): Collection
    {
        $c = collect();
        $supervisor->model->processes()
            ->each(function (SupervisorProcess $process) use ($c) {
                $c->push(count($process->channels));
            });

        return $c;
    }

    public function getMinimumScale(): int
    {
        $default = config('tmi-cluster.auto_scale.processes.min', 2);
        if (!$this->shouldRestoreScale()) {
            return $default;
        }

        return $this->connection()->get('auto-scale:minimum-scale') ?? $default;
    }

    public function setMinimumScale(int $scale)
    {
        return $this->connection()->set('auto-scale:minimum-scale', $scale, 'EX', 60);
    }
}
