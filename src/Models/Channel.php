<?php

namespace GhostZero\TmiCluster\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string id
 * @property bool revoked
 * @property bool reconnect
 */
class Channel extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'tmi_cluster_channels';

    protected $guarded = [];

    protected $casts = [
        'revoked' => 'bool',
        'reconnect' => 'bool',
    ];
}