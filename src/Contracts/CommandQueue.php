<?php

namespace GhostZero\TmiCluster\Contracts;

interface CommandQueue
{
    /**
     * Push a command onto a queue.
     *
     * @param  string  $name
     * @param  string  $command
     * @param  array  $options
     */
    public function push(string $name, string $command, array $options = []);

    /**
     * Get the pending commands for a given queue name.
     *
     * @param  string  $name
     * @return array
     */
    public function pending(string $name): array;

    /**
     * Flush the command queue for a given queue name.
     *
     * @param  string  $name
     * @return void
     */
    public function flush(string $name): void;
}
