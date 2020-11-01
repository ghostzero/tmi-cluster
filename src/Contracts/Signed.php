<?php

namespace GhostZero\TmiCluster\Contracts;

interface Signed
{
    public function signature(): string;
}
