<?php

namespace GhostZero\TmiCluster;

use Closure;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;
use GhostZero\TmiCluster\Contracts\ClusterClient;
use GhostZero\TmiCluster\Contracts\ClusterClientOptions;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Contracts\Pausable;
use GhostZero\TmiCluster\Contracts\Restartable;
use GhostZero\TmiCluster\Contracts\Signed;
use GhostZero\TmiCluster\Contracts\Terminable;
use GhostZero\TmiCluster\Events\IrcCommandEvent;
use GhostZero\TmiCluster\Events\IrcMessageEvent;
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
                $this->exit(4);
            }

            if (!$this->client->isConnected() && !$this->client->getOptions()->shouldReconnect()) {
                $this->exit(5);
            }

            event(new PeriodicTimerCalled());
        });
    }

    private function registerEvents(): void
    {
        $this->client->on('inspector', function (string $url) {
            call_user_func($this->output, null, 'Inspector ready! Visit: ' . $url);
        });

        $this->client
            ->on('message', function ($channel, $tags, $user, $message, $self) {
                if ($self) return;
                $this->event(new IrcMessageEvent($this->client, $channel, $tags, $user, $message, $self), 'message:');
            })
            // forward all irc commands as new IrcCommandEvent
            ->on('cheer', fn() => $this->event(new IrcCommandEvent($this->client, 'cheer', func_get_args())))
            ->on('hosting', fn() => $this->event(new IrcCommandEvent($this->client, 'hosting', func_get_args())))
            ->on('hosted', fn() => $this->event(new IrcCommandEvent($this->client, 'hosted', func_get_args())))
            ->on('raided', fn() => $this->event(new IrcCommandEvent($this->client, 'raided', func_get_args())))
            ->on('subscription', fn() => $this->event(new IrcCommandEvent($this->client, 'subscription', func_get_args())))
            ->on('submysterygift', fn() => $this->event(new IrcCommandEvent($this->client, 'submysterygift', func_get_args())))
            ->on('resub', fn() => $this->event(new IrcCommandEvent($this->client, 'resub', func_get_args())))
            ->on('subgift', fn() => $this->event(new IrcCommandEvent($this->client, 'subgift', func_get_args())))
            ->on('giftpaidupgrade', fn() => $this->event(new IrcCommandEvent($this->client, 'giftpaidupgrade', func_get_args())))
            ->on('anongiftpaidupgrade', fn() => $this->event(new IrcCommandEvent($this->client, 'anongiftpaidupgrade', func_get_args())));
    }

    private function event(Signed $event, $key = 'event:'): void
    {
        $signature = $event->signature();
        if (!$this->lock->get($key . $signature, 300)) {
            return;
        }

        try {
            event($event);
        } catch (Throwable $exception) {
            call_user_func($this->output, null, $exception->getMessage());
            return;
        }

        if ($event instanceof IrcMessageEvent) {
            $this->metrics[self::METRIC_IRC_MESSAGES]++;
        } elseif ($event instanceof IrcCommandEvent) {
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

        call_user_func($this->output, null, 'Evacuate process...');

        // evacuate all current channels to a new process
        TmiCluster::joinNextServer(array_keys($this->client->getChannels()), [$this->model->getKey()]);

        $this->exit($status);
    }

    protected function exit($status = 0): void
    {
        call_user_func($this->output, null, "Got exit signal with code {$status}");

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
                    call_user_func($this->output, null, $command->options->raw_command);
                    $this->client->write($command->options->raw_command);
                    break;
                case CommandQueue::COMMAND_TMI_JOIN:
                    $this->client->join($command->options->channel);
                    break;
                case CommandQueue::COMMAND_TMI_PART:
                    $this->client->part($command->options->channel);
                    break;
                case CommandQueue::COMMAND_CLIENT_EXIT:
                    exit(0);
                default:
                    call_user_func($this->output, null, sprintf('Command %s not supported', $command->command));
            }
        }
    }
}
