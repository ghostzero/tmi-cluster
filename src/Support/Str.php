<?php

namespace GhostZero\TmiCluster\Support;

class Str
{
    public static function convert(float $size): string
    {
        $unit = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}