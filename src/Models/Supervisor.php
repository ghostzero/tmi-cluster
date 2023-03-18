<?php

namespace GhostZero\TmiCluster\Models;

use Carbon\CarbonInterface;
use GhostZero\TmiCluster\Contracts\HasPrometheusMetrics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property string id
 * @property mixed options
 * @property bool is_stale
 * @property CarbonInterface last_ping_at
 * @property SupervisorProcess[]|Collection processes
 * @property array|null metrics
 */
class Supervisor extends Model implements HasPrometheusMetrics
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'tmi_cluster_supervisors';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'metrics' => 'array',
        'last_ping_at' => 'datetime',
    ];

    public function getIsStaleAttribute(): bool
    {
        return $this->last_ping_at->diffInSeconds() >= config('tmi-cluster.supervisor.stale', 300);
    }

    public function processes(): HasMany
    {
        return $this->hasMany(SupervisorProcess::class);
    }

    public function getPrometheusMetrics(): Collection
    {
        return Collection::make([
            'processes' => $this->processes()->count(),
        ])->merge($this->metrics ?? []);
    }
}
