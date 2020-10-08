<?php

namespace GhostZero\TmiCluster;

use Closure;
use GhostZero\Tmi\Channel;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;
use GhostZero\Tmi\Tags;
use GhostZero\TmiCluster\Contracts\ClusterClient;
use GhostZero\TmiCluster\Contracts\ClusterClientOptions;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Events\IrcCommandEvent;
use GhostZero\TmiCluster\Events\IrcMessageEvent;
use GhostZero\TmiCluster\Events\PeriodicTimerCalled;

class TmiClusterClient implements ClusterClient
{
    private Client $client;

    private ClusterClientOptions $options;

    private CommandQueue $commandQueue;

    private Closure $output;

    private function __construct(ClusterClientOptions $options)
    {
        $this->options = $options;
        $this->commandQueue = app(CommandQueue::class);
        $this->client = new Client(new ClientOptions(config('tmi-cluster.tmi')));
        $this->output = function () {
            //
        };

        $this->registerPeriodicTimer();
        $this->registerEvents();
    }

    public static function make(ClusterClientOptions $options): self
    {
        return new self($options);
    }

    public function handleOutputUsing(Closure $output): self
    {
        $this->output = $output;

        return $this;
    }

    private function registerPeriodicTimer(): void
    {
        $this->client->getLoop()->addPeriodicTimer(2, function () {
            $commands = $this->commandQueue->pending($this->getQueueName('input'));
            $commands = array_merge($commands, $this->commandQueue->pending('*'));
            foreach ($commands as $command) {
                if ($command->command === 'tmi:write') {
                    call_user_func($this->output, null, $command->options->raw_command);
                    $this->client->write($command->options->raw_command);
                } elseif ($command->command === 'tmi:join') {
                    $this->client->join($command->options->channel);
                } elseif ($command->command === 'tmi:part') {
                    $this->client->join($command->options->channel);
                }
            }

            event(new PeriodicTimerCalled());
        });
    }

    private function registerEvents(): void
    {
        $this->client
            ->on('message', function (Channel $channel, Tags $tags, string $user, string $message, bool $self) {
                if ($self) return;

                event(new IrcMessageEvent($channel, $tags, $user, $message));
            })
            // forward all irc commands as new IrcCommandEvent
            ->on('cheer', fn() => event(new IrcCommandEvent('cheer', func_get_args())))
            ->on('hosting', fn() => event(new IrcCommandEvent('hosting', func_get_args())))
            ->on('hosted', fn() => event(new IrcCommandEvent('hosted', func_get_args())))
            ->on('raided', fn() => event(new IrcCommandEvent('raided', func_get_args())))
            ->on('subscription', fn() => event(new IrcCommandEvent('subscription', func_get_args())))
            ->on('submysterygift', fn() => event(new IrcCommandEvent('submysterygift', func_get_args())))
            ->on('resub', fn() => event(new IrcCommandEvent('resub', func_get_args())))
            ->on('subgift', fn() => event(new IrcCommandEvent('subgift', func_get_args())))
            ->on('giftpaidupgrade', fn() => event(new IrcCommandEvent('giftpaidupgrade', func_get_args())))
            ->on('anongiftpaidupgrade', fn() => event(new IrcCommandEvent('anongiftpaidupgrade', func_get_args())));
    }

    private function getQueueName(string $string): string
    {
        return $this->options->getUuid() . '-' . $string;
    }

    public function connect(): void
    {
        $this->client->connect();
    }
}
