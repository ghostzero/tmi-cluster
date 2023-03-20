<?php

namespace GhostZero\TmiCluster\Support;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use stdClass;

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
            array_unique(self::merge(...$staleIds)),
            array_unique(self::merge(...$channels)),
            array_unique(self::merge(...$acknowledged)),
        ];
    }

    public static function merge(array|stdClass ...$arrays): array
    {
        return array_merge(...array_map(fn($array) => is_array($array) ? $array : (array)$array, $arrays));
    }
}