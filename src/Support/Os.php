<?php

namespace GhostZero\TmiCluster\Support;

class Os
{
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}