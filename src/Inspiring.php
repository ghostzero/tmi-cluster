<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Models\SupervisorProcess;
use GhostZero\TmiCluster\Support\Composer;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Console\Command;
use Throwable;

class Inspiring
{
    public static function handle(Command $command): void
    {
        try {
            $client = new Client();
            $response = $client->post('https://inspiring.tmi.dev', [
                RequestOptions::JSON => self::getTelemetryData(),
            ]);
            if ($response->getStatusCode() === 200) {
                $lines = explode("\n", $response->getBody()->getContents());
                foreach ($lines as $line) {
                    $command->info($line);
                }
            }
        } catch (Throwable $ex) {
            $command->comment('Unable to fetch inspiring quote.');
        }

        $command->getOutput()->newLine();
    }

    public static function getTelemetryData(): array
    {
        return [
            'php' => [
                'version' => PHP_VERSION,
                'os' => PHP_OS,
            ],
            'tmi_cluster' => [
                'version' => Composer::detectTmiClusterVersion(),
                'configuration' => [
                    'auto_scale' => config('tmi-cluster.auto_scale'),
                    'supervisor' => config('tmi-cluster.supervisor'),
                    'process' => config('tmi-cluster.process'),
                    'throttle' => config('tmi-cluster.throttle'),
                    'channel_manager' => config('tmi-cluster.channel_manager'),
                ],
                'metrics' => [
                    'memory' => memory_get_usage(),
                    'memory_peak' => memory_get_peak_usage(),
                    'memory_limit' => ini_get('memory_limit'),
                    'memory_real' => memory_get_usage(true),
                    'memory_peak_real' => memory_get_peak_usage(true),
                    'channels' => self::getChannelCount(),
                ]
            ],
            'developer' => [
                'name' => config('tmi-cluster.inspiring.developer'),
            ],
        ];
    }

    private static function getChannelCount(): int
    {
        return SupervisorProcess::all()
            ->filter(fn(SupervisorProcess $process) => !$process->is_stale)
            ->map(fn(SupervisorProcess $process) => $process->channels)
            ->collapse()
            ->count();
    }
}