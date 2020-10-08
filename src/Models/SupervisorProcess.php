<?php

namespace GhostZero\TmiCluster\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorProcess extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'channels' => 'array',
    ];
}
