<?php

namespace GhostZero\TmiCluster;

use GhostZero\Tmi\Channel;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;
use GhostZero\Tmi\Tags;
use GhostZero\TmiCluster\Contracts\ClusterClient;
use GhostZero\TmiCluster\Contracts\ClusterClientOptions;
use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Jobs\PeriodicTimerCalled;

class TmiClusterClient implements ClusterClient
{
    private Client $client;

    private ClusterClientOptions $options;

    private CommandQueue $commandQueue;

    public function __construct(ClusterClientOptions $options)
    {
        $this->options = $options;
        $this->commandQueue = app(CommandQueue::class);
        $this->client = new Client(new ClientOptions(config('tmi-cluster.tmi')));

        $this->registerPeriodicTimer();
        $this->registerEvents();
    }

    public function connect(ClusterClientOptions $options): int
    {
        $instance = new self($options);
        $instance->client->connect();

        return 0; // process return code
    }

    private function registerPeriodicTimer(): void
    {
        $this->client->getLoop()->addPeriodicTimer(2, function () {
            foreach ($this->commandQueue->pending($this->getQueueName('input')) as $command) {
                if ($command->command === 'tmi:write') {
                    $this->client->write($command->options['raw_command']);
                }
            }

            PeriodicTimerCalled::dispatch()
                ->onQueue('tmi-cluster')
                ->onConnection(config('tmi-cluster.connection'));
        });
    }

    private function registerEvents(): void
    {
        $this->client
            ->on('message', function (Channel $channel, Tags $tags, string $user, string $message, bool $self) {
                if ($self) return;

                $this->queueEvent('message', func_get_args());
            })
            ->on('cheer', function () {
                $this->queueEvent('cheer', func_get_args());
            })
            ->on('hosting', function () {
                $this->queueEvent('hosting', func_get_args());
            })
            ->on('hosted', function () {
                $this->queueEvent('hosted', func_get_args());
            })
            ->on('raided', function () {
                $this->queueEvent('raided', func_get_args());
            })
            ->on('subscription', function () {
                $this->queueEvent('subscription', func_get_args());
            })
            ->on('submysterygift', function () {
                $this->queueEvent('submysterygift', func_get_args());
            })
            ->on('resub', function () {
                $this->queueEvent('resub', func_get_args());
            })
            ->on('subgift', function () {
                $this->queueEvent('subgift', func_get_args());
            })
            ->on('giftpaidupgrade', function () {
                $this->queueEvent('giftpaidupgrade', func_get_args());
            })
            ->on('anongiftpaidupgrade', function () {
                $this->queueEvent('anongiftpaidupgrade', func_get_args());
            });
    }

    private function getQueueName(string $string): string
    {
        return $this->options->getUuid() . '-' . $string;
    }

    private function queueEvent(string $string, array $payload): void
    {
        $this->commandQueue->push($this->getQueueName('output'), $string, $payload);
    }
}
