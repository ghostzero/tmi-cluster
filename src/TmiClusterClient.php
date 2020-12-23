<?php

namespace GhostZero\TmiCluster;

use Closure;
use Exception;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;
use GhostZero\Tmi\Events\Event;
use GhostZero\Tmi\Events\Inspector\InspectorReadyEvent;
use GhostZero\Tmi\Events\Twitch\MessageEvent;
use GhostZero\TmiCluster\Contracts\ChannelDistributor;
use GhostZero\TmiCluster\Contracts\ClusterClient;
use GhostZero\TmiCluster\Contracts\ClusterClientOptions;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Contracts\Pausable;
use GhostZero\TmiCluster\Contracts\Restartable;
use GhostZero\TmiCluster\Contracts\Terminable;
use GhostZero\TmiCluster\Events\PeriodicTimerCalled;
use GhostZero\TmiCluster\Models\SupervisorProcess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

/**
 *
 * Exit Codes:
 *  3 - ModelNotFoundException: Server started with unknown uuid.
 *  4 - ModelNotFoundException: Someone killed the model.
 *  5 - IRC Client disconnected.
 *  5 - Exit via CommandQueue.
 *
 * Class TmiClusterClient
 * @package GhostZero\TmiCluster
 */
class TmiClusterClient implements ClusterClient, Pausable, Restartable, Terminable
{
    use ListensForSignals;

    private bool $working = true;
    private $model;
    private Client $client;
    private ClusterClientOptions $options;
    private CommandQueue $commandQueue;
    private Closure $output;
    private Lock $lock;
    private AutoCleanup $autoCleanup;

    private const METRIC_IRC_MESSAGES = 'irc_messages';
    private const METRIC_IRC_COMMANDS = 'irc_commands';
    private const METRIC_COMMAND_QUEUE_COMMANDS = 'command_queue_commands';

    private array $metrics = [
        self::METRIC_IRC_MESSAGES => 0,
        self::METRIC_IRC_COMMANDS => 0,
        self::METRIC_COMMAND_QUEUE_COMMANDS => 0,
    ];

    private function __construct(ClusterClientOptions $options, Closure $output)
    {
        $this->output = $output;
        $this->options = $options;
        $this->lock = app(Lock::class);
        $this->autoCleanup = app(AutoCleanup::class);

        try {
            $this->model = SupervisorProcess::query()->whereKey($options->getUuid())->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            $this->exit(3);
        }

        $this->commandQueue = app(CommandQueue::class);
        $this->client = new Client(new ClientOptions(config('tmi-cluster.tmi')));

        $this->listenForSignals();
        $this->registerPeriodicTimer();
        $this->registerEvents();
    }

    public static function make(ClusterClientOptions $options, Closure $output): self
    {
        return new self($options, $output);
    }

    private function registerPeriodicTimer(): void
    {
        $this->client->getLoop()->addPeriodicTimer(2, function () {
            $this->processPendingSignals();

            $this->processPendingCommands();

            // Update all model data here
            $this->model->forceFill([
                'state' => $this->client->isConnected()
                    ? SupervisorProcess::STATE_CONNECTED
                    : SupervisorProcess::STATE_DISCONNECTED,
                'channels' => array_keys($this->client->getChannels()),
                'last_ping_at' => now(),
                'metrics' => $this->getMetrics(),
            ]);

            // Next, we'll persist the process state to storage so that it can be read by a
            // user interface. This contains information on the specific options for it and
            // the current number of clients for easy load monitoring.
            try {
                $this->model->save();
            } catch (ModelNotFoundException $e) {
                $this->terminate(4);
            }

            if (!$this->client->isConnected() && !$this->client->getOptions()->shouldReconnect()) {
                $this->terminate(5);
            }

            event(new PeriodicTimerCalled());
        });

        $cleanupInterval = $this->getCleanupInterval();
        $this->log('Register cleanup loop for every ' . $cleanupInterval . ' sec.');
        $this->client->getLoop()->addPeriodicTimer($cleanupInterval, function () {
            try {
                $this->autoCleanup->cleanup($this);
            } catch (Exception $e) {
                $this->log($e->getTraceAsString());
            }
        });
    }

    private function registerEvents(): void
    {
        $this->client->on(InspectorReadyEvent::class, function (string $url) {
            $this->log('Inspector ready! Visit: ' . $url);
        });

        $this->client->any(fn($e) => $this->event($e));
    }

    private function event(Event $event): void
    {
        if ($event->signature() && !$this->lock->get('event:' . $event->signature(), 300)) {
            return;
        }

        try {
            event($event);
        } catch (Throwable $exception) {
            $this->log($exception->getMessage());
            return;
        }

        if ($event instanceof MessageEvent) {
            $this->metrics[self::METRIC_IRC_MESSAGES]++;
        } elseif ($event instanceof Event) {
            $this->metrics[self::METRIC_IRC_COMMANDS]++;
        }
    }

    private function getMetrics(): array
    {
        return array_merge($this->metrics, [
            'channels' => count($this->client->getChannels()),
        ]);
    }

    private function getQueueName(string $string): string
    {
        return $this->options->getUuid() . '-' . $string;
    }

    public function connect(): void
    {
        $this->client->connect();
    }

    public function pause(): void
    {
        $this->working = false;
    }

    public function continue(): void
    {
        $this->working = true;
    }

    public function restart(): void
    {
        $this->working = true;
    }

    public function terminate($status = 0): void
    {
        $this->working = false;

        // evacuate all current channels to a new process
        app(ChannelDistributor::class)->join(array_keys($this->client->getChannels()), [$this->model->getKey()]);
        $this->log(sprintf('TMI Client evacuated! Migrated: %s', count($this->client->getChannels())));

        $this->exit($status);
    }

    protected function exit($status = 0): void
    {
        $this->log("Got exit signal with code {$status}");

        $this->exitProcess($status);
    }

    protected function exitProcess($status = 0): void
    {
        exit((int)$status);
    }

    private function processPendingCommands(): void
    {
        $commands = $this->commandQueue->pending($this->getQueueName('input'));
        $commands = array_merge($commands, $this->commandQueue->pending(CommandQueue::NAME_ANY_SUPERVISOR));
        foreach ($commands as $command) {
            $this->metrics[self::METRIC_COMMAND_QUEUE_COMMANDS]++;
            switch ($command->command) {
                case CommandQueue::COMMAND_TMI_WRITE:
                    $this->log($command->options->raw_command);
                    $this->client->write($command->options->raw_command);
                    break;
                case CommandQueue::COMMAND_TMI_JOIN:
                    $this->autoCleanup->acquireLock($this, $command->options->channel);
                    $this->client->join($command->options->channel);
                    break;
                case CommandQueue::COMMAND_TMI_PART:
                    $this->client->part($command->options->channel);
                    break;
                case CommandQueue::COMMAND_CLIENT_EXIT:
                    $this->terminate(6);
                    break;
                default:
                    $this->log(sprintf('Command %s not supported', $command->command));
            }
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function log(string $message): void
    {
        call_user_func($this->output, null, $message);
    }

    public function getUuid()
    {
        return $this->model->getKey();
    }

    private function getCleanupInterval(): int
    {
        return config('tmi-cluster.auto_cleanup.interval', 300)
            + random_int(0, max(0, config('tmi-cluster.auto_cleanup.max_delay', 600)));
    }
}
