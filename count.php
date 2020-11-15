<?php

use GhostZero\TmiCluster\AutoCleanup;

require_once __DIR__ . '/vendor/autoload.php';

$stats = json_decode(file_get_contents('https://own3d.pro/tmi-cluster/statistics'), false, 512, JSON_THROW_ON_ERROR);
$ircWorkers = json_decode(file_get_contents('https://notifications-backend.own3d.tv/api/irc-workers'), false, 512, JSON_THROW_ON_ERROR);

function getTmiChannels(stdClass $stats): array
{
    $channels = [];

    foreach ($stats->supervisors as $supervisor) {
        $c = [];
        foreach ($supervisor->processes as $process) {
            $c[] = $process->channels;
        }
        $channels[] = array_merge(...$c);
    }

    return array_merge(...$channels);
}

function getIrcChannels(stdClass $stats): array
{
    $channels = [];

    foreach ($stats->data as $process) {
        $channels[] = $process->channels;
    }

    return array_merge(...$channels);
}

$channels = getTmiChannels($stats);
$channelsB = getIrcChannels($ircWorkers);

$dups = array();
foreach(array_count_values($channels) as $val => $c) {
    if ($c > 1) {
        $dups[] = $val;
    }
}

$diff = AutoCleanup::diff($dups);

dd([
    'dd' => $diff,
    'dups' => count($dups),
    'new' => [
        'count' => count($channels),
        'unique' => count(array_unique($channels)),
    ],
    'old' => [
        'count' => count($channelsB),
        'unique' => count(array_unique($channelsB)),
    ]
]);