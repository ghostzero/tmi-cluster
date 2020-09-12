<?php

namespace GhostZero\TmiCluster\Models;

use GhostZero\TmiCluster\Process\ProcessOptions;
use GhostZero\TmiCluster\Process\ProcessPool;
use Closure;
use DomainException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Throwable;

/**
 * @property mixed name
 * @property mixed options
 */
class Supervisor extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'options' => 'array',
    ];

    public array $processPools = [];

    public bool $working = true;

    private Closure $output;

    public function __construct(array $attributes = [])
    {
        $this->output = static function () {
            //
        };

        // todo flush command queue by supervisor name

        parent::__construct($attributes);
    }

    public function ensureNoDuplicateSupervisors()
    {
        if (self::query()->where(['name' => $this->name])->whereKeyNot($this->getKey())->exists()) {
            throw new DomainException('A Supervisor with this name already exists.');
        }
    }

    public function monitor(): void
    {
        $this->ensureNoDuplicateSupervisors();

        $this->save();

        while (true) {
            sleep(1);

            $this->loop();
        }
    }

    public function handleOutputUsing(Closure $output): void
    {
        $this->output = $output;
    }

    public function scale(int $int): void
    {
        $this->pools()->each(fn(ProcessPool $x) => $x->scale($int));
    }

    private function loop(): void
    {
        try {
            // todo process pending commands

            // If the supervisor is working, we will perform any needed scaling operations and
            // monitor all of these underlying worker processes to make sure they are still
            // processing queued jobs. If they have died, we will restart them each here.
            if ($this->working) {
                $this->autoScale();

                $this->pools()->each(fn(ProcessPool $x) => $x->monitor());
            }

            // Next, we'll persist the supervisor state to storage so that it can be read by a
            // user interface. This contains information on the specific options for it and
            // the current number of worker processes per queue for easy load monitoring.
            $this->save();

            // event(new SupervisorLooped($this));
        } catch (Throwable $e) {
            $this->output(null, $e->getMessage());
            app(ExceptionHandler::class)->report($e);
        }
    }

    private function autoScale(): void
    {
        // app(AutoScale::class)->scale($this);
    }

    private function createProcessPools(): array
    {
        return [$this->createSingleProcessPool(new ProcessOptions($this->options))];
    }

    private function createSingleProcessPool(ProcessOptions $options): ProcessPool
    {
        return new ProcessPool($options, function ($type, $line) {
            $this->output($type, $line);
        });
    }

    public function output($type, $line): void
    {
        call_user_func($this->output, $type, $line);
    }

    private function pools(): Collection
    {
        if (!$this->processPools) {
            $this->processPools = $this->createProcessPools();
        }

        return new Collection($this->processPools);
    }
}
