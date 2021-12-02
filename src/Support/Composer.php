<?php

namespace GhostZero\TmiCluster\Support;

class Composer
{
    public static function detectTmiClusterVersion(): string
    {
        $composerFile = base_path('vendor/composer/installed.json');

        if(!file_exists($composerFile)) {
            return 'unknown';
        }

        $packages = json_decode(file_get_contents($composerFile), true)['packages'];

        foreach ($packages as $package) {
            if ($package['name'] === 'ghostzero/tmi-cluster') {
                return $package['version'];
            }
        }

        return 'unknown';
    }

    public static function isSupportedVersion(string $detectedTmiClusterVersion): bool
    {
        $supportedBranches = [];
        $supportedSemanticVersions = ['2.3', '3.0'];

        if (in_array($detectedTmiClusterVersion, $supportedBranches, true)) {
            return true;
        }

        foreach ($supportedSemanticVersions as $supportedSemanticVersion) {
            if (version_compare($detectedTmiClusterVersion, $supportedSemanticVersion, '>=')) {
                return true;
            }
        }

        return false;
    }
}