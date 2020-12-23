<?php

namespace GhostZero\TmiCluster\Support;

use GhostZero\TmiCluster\Contracts\CommandQueue;

class Arr
{
    public static function unique(array $commands, array $staleIds, array $channels): array
    {
        $staleIds = [$staleIds];
        $channels = [$channels];

        foreach ($commands as $command) {
            if ($command->command !== CommandQueue::COMMAND_TMI_JOIN) {
                continue;
            }

            $staleIds[] = $command->options->staleIds ?? [];
            $channels[] = $command->options->channels ?? [];
        }

        return [array_unique(array_merge(...$staleIds)), array_unique(array_merge(...$channels))];
    }
}