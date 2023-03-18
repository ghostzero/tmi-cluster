<?php

namespace GhostZero\TmiCluster\Http\Controllers;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;

class MetricsController extends Controller
{
    protected array $hintedMetrics = [];

    private const METRIC_HELP_TEXT = [
        'tmi_cluster_supervisor_processes' => 'Number of processes of the supervisor',
        'tmi_cluster_process_channels' => 'Memory usage of the process',
        'tmi_cluster_process_irc_commands' => 'IRC commands processed by the process',
        'tmi_cluster_process_irc_messages' => 'IRC messages processed by the process',
        'tmi_cluster_process_memory_usage' => 'Memory usage of the process',
        'tmi_cluster_process_command_queue_commands' => 'Commands in the command queue',
        'tmi_cluster_avg_usage' => 'Average usage of all processes',
        'tmi_cluster_avg_response_time' => 'Average response time of all processes',
    ];

    private const METRIC_TYPE_HINTS = [
        'tmi_cluster_supervisor_processes' => 'gauge',
        'tmi_cluster_process_channels' => 'gauge',
        'tmi_cluster_process_irc_commands' => 'counter',
        'tmi_cluster_process_irc_messages' => 'counter',
        'tmi_cluster_process_memory_usage' => 'gauge',
        'tmi_cluster_process_command_queue_commands' => 'gauge',
        'tmi_cluster_avg_usage' => 'gauge',
        'tmi_cluster_avg_response_time' => 'gauge',
    ];

    public function handle()
    {
        $metrics = collect();

        /** @var SupervisorRepository $repository */
        $repository = app(SupervisorRepository::class);

        $repository->all()->each(function (Supervisor $supervisor) use (&$metrics) {
            foreach ($supervisor->processes as $process) {
                $metrics->push($this->getProcessMetrics($supervisor, $process));
            }

            $metrics->push($this->getSupervisorMetrics($supervisor));
        });

        $metrics->push($this->getGlobalMetrics());

        return response($metrics->collapse()->join(PHP_EOL))
            ->header('Content-Type', 'text/plain');
    }

    protected function getProcessMetrics(Supervisor $supervisor, SupervisorProcess $process): array
    {
        return $process->getPrometheusMetrics()
            ->map(function ($value, $key) use ($supervisor, $process) {
                return $this->ensureIsDocumented(sprintf('tmi_cluster_process_%s', $key), $value, [
                    'supervisor' => $supervisor->getKey(),
                    'process' => $process->getKey(),
                ]);
            })->values()->toArray();
    }

    protected function getSupervisorMetrics(Supervisor $supervisor): array
    {
        return $supervisor->getPrometheusMetrics()
            ->map(function ($value, $key) use ($supervisor) {
                return $this->ensureIsDocumented(sprintf('tmi_cluster_supervisor_%s', $key), $value, [
                    'supervisor' => $supervisor->getKey(),
                ]);
            })->values()->toArray();
    }

    private function getGlobalMetrics(): array
    {
        return [
            $this->ensureIsDocumented('tmi_cluster_avg_usage', 10),
            $this->ensureIsDocumented('tmi_cluster_avg_response_time', 700)
        ];
    }

    private function ensureIsDocumented(string $key, float $value, array $tags = []): string
    {
        $line = sprintf('%s%s %s', $key, $this->formatTags($tags), $value);

        if (isset($this->hintedMetrics[$key])) {
            return $line;
        }

        $this->hintedMetrics[$key] = true;

        return implode(PHP_EOL, [
            sprintf('# HELP %s %s', $key, self::METRIC_HELP_TEXT[$key] ?? 'N/A'),
            sprintf('# TYPE %s %s', $key, self::METRIC_TYPE_HINTS[$key] ?? 'gauge'),
            $line,
        ]);
    }

    /**
     * Format tags for prometheus.
     */
    private function formatTags(array $tags): string
    {
        $formatted = collect($tags)->map(function ($value, $key) {
            return sprintf('%s="%s"', $key, $value);
        })->join(',');

        return $formatted ? sprintf('{%s}', $formatted) : '';
    }
}
