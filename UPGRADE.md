# Upgrade Guide

When upgrading to any new TMI Cluster version, you should re-publish TMI Cluster's assets:

```bash
php artisan tmi-cluster:publish
```

To ensure TMI Cluster's assets are updated when a new version is downloaded, you may add a Composer hook inside your project's composer.json file to automatically publish TMI Cluster's latest assets:

```bash
"scripts": {
    "post-update-cmd": [
        "@php artisan tmi-cluster:publish --ansi"
    ]
}
```

## Upgrading To 3.0 From 2.x

### TMI Cluster Auto Cleanup

PR: https://github.com/ghostzero/tmi-cluster/pull/14

The ChannelManager now manages the channels and decides whether to remove channels from the TMI cluster. This was previously decided by the Twitch API. Because the API sometimes returns wrong values, we have completely removed the Twitch API.

### TMI Cluster Channel Manager

PR: https://github.com/ghostzero/tmi-cluster/pull/14

Channels must have given an authorization before the TMI cluster joins the channels. This prevents the TMI cluster from joining channels that have revoked authorization, for example.
