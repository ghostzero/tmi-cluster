<?php

namespace GhostZero\TmiCluster;

/**
 * @mixin TmiClusterClient
 */
trait RequestsMemory
{
    protected function requestMemoryLimit(int $memory): bool
    {
        $currentMemory = $this->getMemoryLimit();
        if ($currentMemory < $memory) {
            $this->log(sprintf('Requesting memory limit of %d bytes', $memory));

            return ini_set('memory_limit', $memory);
        }

        return true;
    }

    /** @noinspection PhpMissingBreakStatementInspection */
    protected function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if (is_numeric($memoryLimit)) {
            return (int)$memoryLimit;
        }

        $unit = strtolower(substr($memoryLimit, -1));
        $memoryLimit = (int)$memoryLimit;

        switch ($unit) {
            case 'g':
                $memoryLimit *= 1024;
            // no break (cumulative multiplier)
            case 'm':
                $memoryLimit *= 1024;
            // no break (cumulative multiplier)
            case 'k':
                $memoryLimit *= 1024;
        }

        return $memoryLimit;
    }
}