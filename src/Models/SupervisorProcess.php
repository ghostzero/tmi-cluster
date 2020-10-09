<?php

namespace GhostZero\TmiCluster\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property mixed supervisor_id
 * @property mixed state
 * @property mixed channels
 * @property Supervisor supervisor
 * @property CarbonInterface last_ping_at
 * @property bool is_stale
 */
class SupervisorProcess extends Model
{
    public const STATE_INITIALIZE = 'initialize';
    public const STATE_CONNECTED = 'connected';
    public const STATE_DISCONNECTED = 'disconnected';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $dates = [
        'last_ping_at',
    ];

    protected $casts = [
        'channels' => 'array',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function getIsStaleAttribute(): bool
    {
        return $this->last_ping_at->diffInSeconds() >= 30;
    }
}
