<?php

namespace Donmbelembe\LaravelWso2is;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class Wso2is
{
    /**
     * Ensure WSO2IS is configured.
     */
    public static function configure(): void
    {
        if (! config('services.wso2is.base_url')) {
            throw new RuntimeException("The 'services.wso2is.base_url' configuration value is undefined.");
        }

        if (! config('services.wso2is.client_id')) {
            throw new RuntimeException("The 'services.wso2is.client_id' configuration value is undefined.");
        }

        if (! config('services.wso2is.client_secret')) {
            throw new RuntimeException("The 'services.wso2is.client_secret' configuration value is undefined.");
        }

        if (! config('services.wso2is.redirect_uri')) {
            throw new RuntimeException("The 'services.wso2is.redirect_uri' configuration value is undefined.");
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
        $response = Http::asForm()->post(config('services.wso2is.base_url') . '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('services.wso2is.client_id'),
            'client_secret' => config('services.wso2is.client_secret'),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to refresh access token.');
        }

        $data = $response->json();

        return [
            $data['access_token'],
            $data['refresh_token'] ?? $refreshToken, // Some flows might not return new refresh token
        ];
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
