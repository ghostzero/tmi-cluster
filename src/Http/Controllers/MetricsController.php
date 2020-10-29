<?php

namespace GhostZero\TmiCluster\Http\Controllers;

use GhostZero\TmiCluster\Contracts\SupervisorRepository;
use GhostZero\TmiCluster\Models\Supervisor;
use GhostZero\TmiCluster\Models\SupervisorProcess;

class MetricsController extends Controller
{
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
        return collect($process->metrics)->filter()
            ->map(function ($value, $key) use ($supervisor, $process) {
                return sprintf(
                    'tmi_cluster_process_%s{supervisor="%s",process="%s"} %s',
                    $key,
                    $supervisor->getKey(),
                    $process->getKey(),
                    $value,
                );
            })->values()->toArray();
    }

    protected function getSupervisorMetrics(Supervisor $supervisor): array
    {
        return collect($supervisor->metrics)->filter()
            ->map(function ($value, $key) use ($supervisor) {
                return sprintf(
                    'tmi_cluster_supervisor_%s{supervisor="%s"} %s',
                    $key,
                    $supervisor->getKey(),
                    $value,
                );
            })->values()->toArray();
    }

    private function getGlobalMetrics(): array
    {
        return [
            'tmi_cluster_avg_usage 10',
            'tmi_cluster_avg_response_time 700',
        ];
    }
}
