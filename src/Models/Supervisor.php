<?php

namespace GhostZero\TmiCluster\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property mixed name
 * @property mixed options
 * @property bool is_stale
 * @property CarbonInterface last_ping_at
 * @property SupervisorProcess[]|Collection processes
 */
class Supervisor extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $dates = [
        'last_ping_at',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function getIsStaleAttribute(): bool
    {
        return $this->last_ping_at->diffInSeconds() >= 30;
    }

    public function processes(): HasMany
    {
        return $this->hasMany(SupervisorProcess::class);
    }
}
