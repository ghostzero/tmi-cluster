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
            array_unique(self::tryArrayMerge('$staleIds', ...$staleIds)),
            array_unique(self::tryArrayMerge('$channels', ...$channels)),
            array_unique(self::tryArrayMerge('$acknowledged', ...$acknowledged)),
        ];
    }

    private static function tryArrayMerge(string $ident, array ...$arrays): array
    {
        try {
            return array_merge(...$arrays);
            // handle argument #971 must be of type array, stdClass given
        } catch (Throwable $e) {
            Log::error($e->getMessage() . ' of ' . $ident, [
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}