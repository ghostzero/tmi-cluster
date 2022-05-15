<?php

declare(ticks=1);

namespace GhostZero\TmiCluster\Commands;

use DomainException;
use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Events\SupervisorLooped;
use GhostZero\TmiCluster\Supervisor;
use GhostZero\TmiCluster\Support\Composer;
use GhostZero\TmiCluster\Watchable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class TmiClusterCommand extends Command
{
    use Watchable;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmi-cluster
                            {--watch : Automatically reload the supervisor when the application is modified}
                            {--poll : Use file system polling while watching in order to watch files over a network}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts a new tmi cluster supervisor.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->printMotd();

        try {
            /** @var Supervisor $supervisor */
            $supervisor = app(SupervisorRepository::class)->create();
        } catch (DomainException $e) {
            $this->error('A supervisor with this name is already running.');

            return 13;
        }

        $this->info("Supervisor {$supervisor->model->getKey()} started successfully!");
        $this->info('Press Ctrl+C to stop the supervisor safely.');

        $this->getOutput()->newLine();

        return $this->start($supervisor);
    }

    private function start(Supervisor $supervisor): int
    {
        if ($supervisor->model->options['nice']) {
            proc_nice($supervisor->model->options['nice']);
        }

        $supervisor->handleOutputUsing(function ($type, $line) {
            $method = $this->matchOutputType($type);
            $time = date('Y-m-d H:i:s');
            $this->{$method}('[' . $time . '] ' . trim($line));
        });

        $supervisor->scale(config('tmi-cluster.auto_scale.processes.min'));

        if ($this->option('watch')) {
            $watcher = $this->startServerWatcher();
            Event::listen(function (SupervisorLooped $event) use ($watcher) {
                if ($watcher->isRunning() &&
                    $watcher->getIncrementalOutput()) {
                    $this->info('Application change detected. Restarting supervisor…');
                    $event->supervisor->restart();
                } elseif ($watcher->isTerminated()) {
                    $this->error(
                        'Watcher process has terminated. Please ensure Node and chokidar are installed.' . PHP_EOL .
                        $watcher->getErrorOutput()
                    );
                }
            });
        }

        $supervisor->monitor();

        return 0;
    }

    private function printMotd(): void
    {
        $lines = file_get_contents(__DIR__ . '/../../resources/cli/motd.txt');
        $lines = explode("\n", $lines);
        foreach ($lines as $line) {
            $this->comment($line);
        }

        $detectedTmiClusterVersion = Composer::detectTmiClusterVersion();
        $this->comment(sprintf('You are running version %s of tmi-cluster.', $detectedTmiClusterVersion));

        // print an unsupported message if the detected version is not within the supported range of dev-master or semantic version of ^2.3 or ^3.0
        if (!Composer::isSupportedVersion($detectedTmiClusterVersion)) {
            $this->warn('This version of tmi-cluster is not supported. Please upgrade to the latest version.');
        }

        $this->getOutput()->newLine();
    }

    private function matchOutputType(?string $type): string
    {
        switch ($type) {
            case null:
            case 'info':
            case 'out':
                return 'info';
            case 'error':
            case 'err':
                return 'error';
            case 'comment':
                return 'comment';
            case 'question':
                return 'question';
            default:
                throw new DomainException(sprintf('Unknown output type "%s"', $type));
        }
    }
}
