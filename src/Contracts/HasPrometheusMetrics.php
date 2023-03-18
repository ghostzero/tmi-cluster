<?php

namespace GhostZero\TmiCluster\Contracts;

use Illuminate\Support\Collection;

interface HasPrometheusMetrics
{
    /**
     * Returns a collection of metrics that should be exposed to Prometheus.
     *
     * @return Collection<string, mixed>
     */
    public function getPrometheusMetrics(): Collection;
}