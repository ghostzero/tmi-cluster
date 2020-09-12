<?php

namespace GhostZero\TmiCluster\Commands;

use GhostZero\Tmi\Channel;
use GhostZero\Tmi\Client;
use GhostZero\Tmi\ClientOptions;
use GhostZero\Tmi\Tags;
use Illuminate\Console\Command;

class TmiWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi:work';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = new Client(new ClientOptions(config('tmi-cluster.tmi')));

        $client->getLoop()->addPeriodicTimer(2, function () use ($client) {
            $client->say('ghostzero', 'Hello World!');
        });

        $client->on('topic', function (string $channel, string $topic) {
            $this->info(sprintf('Topic: %s: %s', $channel, $topic));
        });

        $client->on('names', function (string $channel, array $nicks) {
            $this->info(sprintf('Names: %s: %s', $channel, implode(', ', $nicks)));
        });

        $client->on('message', function (Channel $channel, Tags $tags, string $user, string $message, bool $self) use ($client) {
            $this->info(sprintf('Message: %s @ %s: %s', $channel->getName(), $user, $message));
            // if ($self) return;

            $this->info('Tags: ' . json_encode($tags->getTags(), JSON_THROW_ON_ERROR));

            if (strtolower($message) === '!hello') {
                $client->say($channel->getName(), "@{$user}, heya!");
            }
        });

        $client->on('cheer', function (Channel $channel, Tags $tags, string $user, string $message, bool $self) use ($client) {
            $client->say($channel->getName(), "@{$user}, thanks for {$tags['bits']} bits!");
        });

        $client->on('ping', function () {
            $this->info('Ping received');
        });

        $this->info('Starting irc connection...');

        $client->connect();

        return 0;
    }
}
