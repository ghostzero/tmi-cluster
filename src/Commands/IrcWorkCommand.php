<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Jerodev\PhpIrcClient\IrcChannel;
use Jerodev\PhpIrcClient\IrcClient;
use Jerodev\PhpIrcClient\Options\ClientOptions;

class IrcWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'irc:work';

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
        $oauth = 'oauth:ygr801k4rn5sv0j2nvzu2dm7rwgoyy';

        $options = new ClientOptions('ghostzero', ['#ghostzero']);
        $options->autoConnect = true;
        $options->floodProtectionDelay = 750;

        $this->info('spawn  client');
        $client = new IrcClient('irc.chat.twitch.tv:6667', $options);
        $this->info('connect');
        $client->setUser();
        $client->connect();
        $this->info('connected');
        $this->info('spawned');
        $client->on('registered', function () {
            $this->info('registered');
        });

        $client->on('motd', function (string $motd) {
            $this->info($motd);
        });

        $client->on('message', function (string $from, IrcChannel $channel, string $message) {
            $this->info($from . $message);
        });

        $client->send('PASS ' . $oauth);
        $client->send('NICK ghostzero');

        return 0;
    }
}
