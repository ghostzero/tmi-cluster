<?php

namespace GhostZero\TmiCluster\Contracts;

interface CommandQueue
{
    /**
     * Sends a raw irc command. Avoid this for join/part commands.
     *
     * Payload: [raw_command => #example]
     */
    public const COMMAND_TMI_WRITE = 'tmi:write';

    /**
     * Joins a irc channel.
     *
     * Payload: [channel => #example]
     */
    public const COMMAND_TMI_JOIN = 'tmi:join';

    /**
     * Leaves a irc channel.
     *
     * Payload: [channel => #example]
     */
    public const COMMAND_TMI_PART = 'tmi:part';

    /**
     * Executes a exit(0) within the tmi cluster process.
     */
    public const COMMAND_CLIENT_EXIT = 'client:exit';

    /**
     * Scales out the supervisor by one process.
     */
    public const COMMAND_SUPERVISOR_SCALE_OUT = 'supervisor:scale-out';

    /**
     * Scales in the supervisor by one process.
     */
    public const COMMAND_SUPERVISOR_SCALE_IN = 'supervisor:scale-in';

    /**
     * Queue to handle lost and found cases. Eg. a channel cannot be joined.
     */
    public const NAME_LOST_AND_FOUND = 'lost-and-found';

    /**
     * Supervisor queue: First-come, First-served.
     */
    public const NAME_ANY_SUPERVISOR = '*';

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
