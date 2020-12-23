<?php


namespace GhostZero\TmiCluster\Tests\TestCases;


use GhostZero\TmiCluster\AutoScale;
use GhostZero\TmiCluster\Tests\TestCase;

class AutoScaleTest extends TestCase
{
    public function testSetRestore()
    {
        $this->setRestore(false);
        self::assertFalse($this->getAutoScale()->shouldRestoreScale());

        $this->setRestore(true);
        self::assertTrue($this->getAutoScale()->shouldRestoreScale());
    }

    public function testRestoreScale()
    {
        $config = config('tmi-cluster.auto_scale.processes.min');
        $this->getAutoScale()->setMinimumScale(7);

        // the value should be get from the config
        $this->setRestore(false);
        self::assertEquals($config, $this->getAutoScale()->getMinimumScale());

        // the value should be get from the redis
        $this->setRestore(true);
        self::assertNotEquals($config, $scale = $this->getAutoScale()->getMinimumScale());
        self::assertEquals(7, $scale);

    }

    private function setRestore(bool $restore)
    {
        app('config')->set('tmi-cluster.auto_scale.restore', $restore);
    }

    private function getAutoScale(): AutoScale
    {
        return app(AutoScale::class);
    }
}