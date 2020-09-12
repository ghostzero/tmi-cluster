<?php

namespace GhostZero\TmiCluster\Facades;

use GhostZero\TmiCluster\TmiCluster as TmiClusterService;
use Illuminate\Support\Facades\Facade;

class TmiCluster extends Facade
{
    protected static function getFacadeAccessor()
    {
        return TmiClusterService::class;
    }
}
