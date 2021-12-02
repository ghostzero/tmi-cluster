<?php

namespace GhostZero\TmiCluster\Tests\TestCases;

use GhostZero\TmiCluster\Contracts\CommandQueue;
use GhostZero\TmiCluster\Support\Arr;
use GhostZero\TmiCluster\Support\Composer;
use GhostZero\TmiCluster\Tests\TestCase;
use JsonException;

class ComposerTest extends TestCase
{
    public function testDetectTmiClusterVersion(): void
    {
        $this->assertEquals(
            'unknown',
            Composer::detectTmiClusterVersion()
        );
    }

    public function testIsSupportedVersion(): void
    {
        $this->assertFalse(
            Composer::isSupportedVersion('1.0.0')
        );

        $this->assertFalse(
            Composer::isSupportedVersion('2.1.0')
        );

        $this->assertTrue(
            Composer::isSupportedVersion('2.3.0')
        );

        $this->assertFalse(
            Composer::isSupportedVersion('dev-master')
        );

        $this->assertFalse(
            Composer::isSupportedVersion('dev-develop')
        );

        $this->assertTrue(
            Composer::isSupportedVersion('3.0.0')
        );

        $this->assertFalse(
            Composer::isSupportedVersion('dev-develop')
        );

    }

}