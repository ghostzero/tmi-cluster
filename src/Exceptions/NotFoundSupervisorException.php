<?php

namespace GhostZero\TmiCluster\Exceptions;

use DomainException;

class NotFoundSupervisorException extends DomainException
{
    public static function fromJoinNextServer(): self
    {
        return new self('There are no supervisors to join.');
    }
}
