<?php

namespace GhostZero\TmiCluster\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Scope;
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
class Supervisor extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $dates = [
        'last_ping_at',
    ];

    protected $casts = [
        'options' => 'array',
        'metrics' => 'array',
    ];

    public function getIsStaleAttribute(): bool
    {
        return $this->last_ping_at->diffInSeconds() >= 30;
    }

    public function processes(): HasMany
    {
        $this->morphInstanceTo()

        return $this->hasMany(SupervisorProcess::class);
    }




}
