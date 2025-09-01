<?php

namespace Notrel\LaravelWso2is;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class Wso2is
{
    protected static ?\Notrel\LaravelWso2is\Http\Client $clientInstance = null;

    /**
     * Configuration keys required for WSO2IS
     */
    protected static array $requiredConfig = [
        'services.wso2is.base_url',
        'services.wso2is.client_id',
        'services.wso2is.client_secret',
        'services.wso2is.redirect_uri',
    ];

    /**
     * Ensure WSO2IS is configured.
     */
    public static function configure(): void
    {
        foreach (static::$requiredConfig as $key) {
            if (! config($key)) {
                throw new RuntimeException("The '{$key}' configuration value is undefined.");
            }
        }
    }

    /**
     * Ensure the given access token is valid, refreshing it if necessary.
     */
    public static function ensureAccessTokenIsValid(string $accessToken, string $refreshToken): array
    {
        static::configure();

        // Validate access token by making a userinfo request
        if (static::validateAccessToken($accessToken)) {
            return [$accessToken, $refreshToken];
        }

        // If access token is invalid, try to refresh it
        return static::refreshAccessToken($refreshToken);
    }

    /**
     * Validate an access token by making a userinfo request.
     */
    protected static function validateAccessToken(string $accessToken): bool
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(config('services.wso2is.base_url') . '/oauth2/userinfo');

            return $response->successful();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Refresh an access token using the refresh token.
     */
    protected static function refreshAccessToken(string $refreshToken): array
    {
        $client = static::client();
        return $client->refreshAccessToken($refreshToken);
    }

    /**
     * Get a configured HTTP client instance (cached)
     */
    public static function client(): \Notrel\LaravelWso2is\Http\Client
    {
        if (static::$clientInstance === null) {
            static::configure();

            static::$clientInstance = new \Notrel\LaravelWso2is\Http\Client(
                config('services.wso2is.base_url'),
                config('services.wso2is.client_id'),
                config('services.wso2is.client_secret')
            );
        }

        return static::$clientInstance;
    }

    /**
     * Reset the cached client instance (useful for testing)
     */
    public static function resetClient(): void
    {
        static::$clientInstance = null;
    }

    /**
     * Get user information from an access token.
     */
    public static function getUserFromToken(string $accessToken): ?User
    {
        try {
            $response = Http::withToken($accessToken)
                ->get(config('services.wso2is.base_url') . '/oauth2/userinfo');

            if (! $response->successful()) {
                return null;
            }

            $userData = $response->json();

            return new User(
                id: $userData['sub'] ?? $userData['id'],
                firstName: $userData['given_name'] ?? null,
                lastName: $userData['family_name'] ?? null,
                email: $userData['email'],
                username: $userData['username'] ?? $userData['preferred_username'] ?? null,
                groups: $userData['groups'] ?? [],
                roles: $userData['roles'] ?? [],
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Get the OIDC discovery document (cached for performance).
     */
    public static function getDiscoveryDocument(): array
    {
        return Cache::remember('wso2is:discovery', now()->addHours(12), function () {
            $response = Http::get(config('services.wso2is.base_url') . '/oauth2/oidcdiscovery');

            if (! $response->successful()) {
                throw new RuntimeException('Failed to retrieve OIDC discovery document.');
            }

            return $response->json();
        });
    }

    /**
     * Get the JWKs (JSON Web Key Set) for token validation.
     */
    public static function getJwks(): array
    {
        return Cache::remember('wso2is:jwks', now()->addHours(12), function () {
            $discovery = static::getDiscoveryDocument();
            $response = Http::get($discovery['jwks_uri']);

            if (! $response->successful()) {
                throw new RuntimeException('Failed to retrieve JWKs.');
            }

            return $response->json();
        });
    }
}
