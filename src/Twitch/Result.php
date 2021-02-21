<?php

namespace GhostZero\TmiCluster\Twitch;

class Result
{
    private bool $success;
    /** @var mixed */
    private $data;
    private string $error;

    public function __construct($success, $data, $error)
    {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
    }

    /**
     * Returns whether the query was successful.
     *
     * @return bool Success state
     */
    public function success(): bool
    {
        return $this->success;
    }

    /**
     * Get the response data, also available as public attribute.
     *
     * @return mixed
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Returns the last HTTP or API error.
     *
     * @return string Error message
     */
    public function error(): string
    {
        return $this->error;
    }
}