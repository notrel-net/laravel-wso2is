<?php

namespace Notrel\LaravelWso2is\Http;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Notrel\LaravelWso2is\Resources\User;
use Notrel\LaravelWso2is\Resources\Group;
use Notrel\LaravelWso2is\Resources\Application;

class Client
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected ?string $accessToken = null;

    protected ?User $users = null;
    protected ?Group $groups = null;
    protected ?Application $applications = null;

    public function __construct(string $baseUrl, string $clientId, string $clientSecret)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Get OAuth2 access token with specified scopes (defaults to management scopes)
     */
    public function getAccessToken(?array $scopes = null): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $scopes = $scopes ?? $this->getManagementScopes();

        $response = Http::asForm()->post($this->baseUrl . '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => implode(' ', $scopes),
        ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    /**
     * Get management scopes for admin operations
     */
    protected function getManagementScopes(): array
    {
        return config('services.wso2is.management_scopes', [
            'internal_user_mgt_create',
            'internal_user_mgt_list',
            'internal_user_mgt_view',
            'internal_user_mgt_update',
            'internal_user_mgt_delete',
            'internal_group_mgt_create',
            'internal_group_mgt_list',
            'internal_group_mgt_view',
            'internal_group_mgt_update',
            'internal_group_mgt_delete',
            'internal_application_mgt_create',
            'internal_application_mgt_view',
            'internal_application_mgt_update',
            'internal_application_mgt_delete'
        ]);
    }

    /**
     * Get OIDC scopes for user authentication
     */
    protected function getOidcScopes(): array
    {
        return config('services.wso2is.scopes', ['openid', 'profile', 'email']);
    }

    /**
     * Refresh an access token using the refresh token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        $response = Http::asForm()->post($this->baseUrl . '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (! $response->successful()) {
            throw new RequestException($response);
        }

        $data = $response->json();

        return [
            $data['access_token'],
            $data['refresh_token'] ?? $refreshToken, // Some flows might not return new refresh token
        ];
    }

    /**
     * Make authenticated request to WSO2IS API with specified scopes
     */
    public function request(string $method, string $endpoint, array $data = [], ?array $scopes = null): array
    {
        $token = $this->getAccessToken($scopes);
        $http = Http::withToken($token)
            ->withHeaders([
                'Accept' => 'application/scim+json',
                'Content-Type' => 'application/scim+json',
            ]);

        $this->configureSSLVerification($http);

        $response = match ($method) {
            'GET' => $http->get($this->baseUrl . $endpoint, $data),
            'POST' => $http->post($this->baseUrl . $endpoint, $data),
            'PUT' => $http->put($this->baseUrl . $endpoint, $data),
            'DELETE' => $http->delete($this->baseUrl . $endpoint, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
        };

        if ($response->failed()) {
            throw new RequestException($response);
        }

        // Handle empty responses (common in DELETE operations)
        $body = $response->body();
        if (empty($body)) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Configure SSL verification for HTTP client
     */
    protected function configureSSLVerification($http): void
    {
        if (
            config('services.wso2is.skip_ssl_verify', false) ||
            (isset($_ENV['WSO2IS_SKIP_SSL_VERIFY']) && $_ENV['WSO2IS_SKIP_SSL_VERIFY'])
        ) {
            $http->withoutVerifying();
        }
    }

    /**
     * Get method shortcut
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Post method shortcut
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Put method shortcut
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Delete method shortcut
     */
    public function delete(string $endpoint, array $data = []): array
    {
        return $this->request('DELETE', $endpoint, $data);
    }

    /**
     * Get Users resource
     */
    public function users(): User
    {
        return $this->users ??= new User($this);
    }

    /**
     * Get Groups resource
     */
    public function groups(): Group
    {
        return $this->groups ??= new Group($this);
    }

    /**
     * Get Applications resource
     */
    public function applications(): Application
    {
        return $this->applications ??= new Application($this);
    }

    /**
     * Reset cached access token (useful for testing or token refresh)
     */
    public function resetAccessToken(): void
    {
        $this->accessToken = null;
    }
}
