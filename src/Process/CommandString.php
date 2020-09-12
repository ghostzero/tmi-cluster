<?php

namespace GhostZero\TmiCluster\Process;

use GhostZero\TmiCluster\PhpBinary;

class CommandString
{
    public static string $command = 'exec @php artisan tmi-cluster:process';

    public static function fromOptions(ProcessOptions $options, string $uuid): string
    {
        $command = str_replace('@php', PhpBinary::path(), static::$command);

        return sprintf(
            "%s %s --supervisor=%s",
            $command, $uuid, $options->getSupervisor()
        );
    }
}
