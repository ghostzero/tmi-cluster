<?php

namespace App\Console\Commands;

use App\Models\Supervisor;
use Exception;
use Illuminate\Console\Command;

class IrcCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'irc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /** @var Supervisor $supervisor */
        $supervisor = Supervisor::factory()->makeOne();

        try {
            $supervisor->ensureNoDuplicateSupervisors();
        } catch (Exception $e) {
            $this->error('A supervisor with this name is already running.');

            return 13;
        }

        $this->info('Kitsune started successfully!');

        return $this->start($supervisor);
    }

    private function start(Supervisor $supervisor): int
    {
        if ($supervisor->options['nice']) {
            proc_nice($supervisor->options['nice']);
        }

        $supervisor->handleOutputUsing(function ($type, $line) {
            $this->output->write($line);
        });

        $supervisor->scale(3);

        $supervisor->monitor();

        return 0;
    }
}
