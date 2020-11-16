<?php

namespace GhostZero\TmiCluster\Twitch\Concerns;

use GhostZero\TmiCluster\Twitch\Twitch;
use Illuminate\Contracts\Cache\Repository;
use romanzipp\Twitch\Enums\GrantType;
use romanzipp\Twitch\Objects\AccessToken;
use romanzipp\Twitch\Result;

/**
 * @mixin Twitch
 */
trait AuthenticationTrait
{
    protected function shouldFetchClientCredentials(): bool
    {
        return $this->hasClientId() && $this->hasClientSecret();
    }

    protected function shouldCacheClientCredentials(): bool
    {
        return config('tmi-cluster.helix.oauth_client_credentials.cache');
    }

    protected function getClientCredentials(): ?AccessToken
    {
        if ($this->shouldCacheClientCredentials() && $token = $this->getCachedClientCredentialsToken()) {
            return $token;
        }

        $result = $this->getOAuthToken(null, GrantType::CLIENT_CREDENTIALS);

        if (!$result->success()) {
            return null;
        }

        $token = new AccessToken(
            (array)$result->data()
        );

        if ($this->shouldCacheClientCredentials()) {
            $this->storeClientCredentialsToken($token);
        }

        return $token;
    }

    protected function getCachedClientCredentialsToken(): ?AccessToken
    {
        $key = config('tmi-cluster.helix.oauth_client_credentials.cache_key');

        $stored = $this->getClientCredentialsCacheRepository()->get($key);

        if (empty($stored)) {
            return null;
        }

        $token = new AccessToken($stored);

        if (!$token->isExpired()) {
            return $token;
        }

        $this->getClientCredentialsCacheRepository()->delete($key);

        return null;
    }

    protected function storeClientCredentialsToken(AccessToken $token): void
    {
        $this->getClientCredentialsCacheRepository()->set(
            config('tmi-cluster.helix.oauth_client_credentials.cache_key'),
            $token->toArray()
        );
    }

    protected function getClientCredentialsCacheRepository(): Repository
    {
        return $this->redisRepository;
    }

    public function isAuthenticationUri(string $uri): bool
    {
        return 0 === strpos($uri, self::OAUTH_BASE_URI);
    }

    abstract public function getOAuthToken(?string $code = null, string $grantType = GrantType::AUTHORIZATION_CODE, array $scopes = []): Result;

    abstract public function hasClientId(): bool;

    abstract public function hasClientSecret(): bool;
}