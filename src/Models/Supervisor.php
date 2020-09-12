<?php

namespace GhostZero\TmiCluster\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed name
 * @property mixed options
 * @property bool is_stale
 */
class Supervisor extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];

    public function getIsStaleAttribute(): bool
    {
        return false;
    }
}
