<?php

namespace GhostZero\TmiCluster\Support;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class Arr
{
    /**
     * @throws Throwable
     */
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
            array_unique(self::castedArrayMerge(...$staleIds)),
            array_unique(self::castedArrayMerge(...$channels)),
            array_unique(self::castedArrayMerge(...$acknowledged)),
        ];
    }

    private static function castedArrayMerge(array ...$arrays): array
    {
        // cast objects to array to prevent error
        return array_merge(...array_map(fn($array) => (array)$array, $arrays));
    }
}