<?php

namespace GhostZero\TmiCluster\Support;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        try {
            return [
                array_unique(array_merge(...$staleIds)),
                array_unique(array_merge(...$channels)),
                array_unique(array_merge(...$acknowledged)),
            ];
            // handle rgument #971 must be of type array, stdClass given
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            Log::error(print_r($staleIds, true));
            Log::error(print_r($channels, true));
            Log::error(print_r($acknowledged, true));

            throw $e;
        }
    }
}