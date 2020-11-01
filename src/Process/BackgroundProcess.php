<?php

namespace GhostZero\TmiCluster\Process;

use Symfony\Component\Process\Process as SystemProcess;

class BackgroundProcess extends SystemProcess
{
    /**
     * @noinspection MagicMethodsValidityInspection
     */
    public function __destruct()
    {
        //
    }
}
