<?php

namespace GhostZero\TmiCluster\Process;

use GhostZero\TmiCluster\PhpBinary;
use GhostZero\TmiCluster\Support\Os;

class CommandString
{
    public static string $command = 'exec @php artisan tmi-cluster:process';
    public static string $commandWindows = 'start /b @php artisan tmi-cluster:process';

    public static function fromOptions(ProcessOptions $options, string $uuid): string
    {
        $command = str_replace('@php', PhpBinary::path(), Os::isWindows() ? static::$commandWindows : static::$command);

        return sprintf(
            "%s %s %s",
            $command, $uuid, static::toOptionsString($options)
        );
    }

    public static function toOptionsString(ProcessOptions $options): string
    {
        return sprintf('--supervisor=%s --memory=%s',
            $options->getSupervisor(),
            $options->getMemory()
        );
    }
}
