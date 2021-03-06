<?php

namespace GhostZero\TmiCluster\Support;

use GhostZero\TmiCluster\Contracts\CommandQueue;

class Arr
{
    public static function unique(array $commands, array $staleIds, array $channels, bool $acknowledge = true): array
    {
        $staleIds = [$staleIds];
        $channels = [$channels];

        $acknowledged = $acknowledge ? $channels : [];

        foreach ($commands as $command) {
            if ($command->command !== CommandQueue::COMMAND_TMI_JOIN) {
                continue;
            }

            $staleIds[] = $command->options->staleIds ?? [];
            $channels[] = $command->options->channels ?? [];
            $shouldAcknowledge = $command->options->acknowledge ?? false;

            if ($shouldAcknowledge) {
                $acknowledged[] = $command->options->channels ?? [];
            }
        }

        return [
            array_unique(array_merge(...$staleIds)),
            array_unique(array_merge(...$channels)),
            array_unique(array_merge(...$acknowledged)),
        ];
    }
}