<?php

namespace GhostZero\TmiCluster\Support;

use Illuminate\Support\Facades\URL as LaravelUrl;
use Illuminate\Support\HtmlString;

class Url
{
    public static function asset(string $path): HtmlString
    {
        if (!$assetUrl = self::getAssetUrl()) {
            return new HtmlString(LaravelUrl::asset($path));
        }

        return new HtmlString(rtrim($assetUrl, '/') . '/' . $path);
    }

    private static function getAssetUrl(): ?string
    {
        return config('tmi-cluster.asset_url');
    }
}