<?php

namespace GhostZero\TmiCluster\Models;

use Carbon\CarbonInterface;
use GhostZero\TmiCluster\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string id
 * @property string supervisor_id
 * @property string state
 * @property array channels
 * @property Supervisor supervisor
 * @property CarbonInterface last_ping_at
 * @property bool is_stale
 * @property string memory_usage
 * @property array|null metrics
 */
class SupervisorProcess extends Model
{
    public const STATE_INITIALIZE = 'initialize';
    public const STATE_CONNECTED = 'connected';
    public const STATE_DISCONNECTED = 'disconnected';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $dates = [
        'last_ping_at',
    ];

    protected $casts = [
        'channels' => 'array',
        'metrics' => 'array',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function getIsStaleAttribute(): bool
    {
        // we require at least 60 seconds for our restart cooldown
        return $this->last_ping_at->diffInSeconds() >= 90;
    }

    public function getMemoryUsageAttribute(): string
    {
        return isset($this->metrics['memory_usage']) ? Str::convert($this->metrics['memory_usage']) : 'N/A';
    }
}
