<?php

namespace Notrel\LaravelWso2is\Http;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
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
    protected ?string $wso2isVersion = null;

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
     * Get OAuth2 access token from WSO2IS (OIDC Compliant)
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        // OIDC Compliant: Use Client Credentials Grant for machine-to-machine
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json'
        ])
            ->withoutVerifying(isset($_ENV['WSO2IS_SKIP_SSL_VERIFY']) && $_ENV['WSO2IS_SKIP_SSL_VERIFY'])
            ->asForm()
            ->post($this->baseUrl . '/oauth2/token', [
                'grant_type' => 'client_credentials',
                'scope' => implode(' ', [
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
                ])
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    /**
     * Make authenticated request to WSO2IS API (OIDC Compliant)
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        // Use OAuth2 bearer token (OIDC Compliant)
        $token = $this->getAccessToken();
        $http = Http::withToken($token)
            ->withHeaders([
                'Accept' => 'application/scim+json',
                'Content-Type' => 'application/scim+json',
            ]);

        // Add SSL verification bypass for development/testing
        if (config('services.wso2is.skip_ssl_verify', false) || (isset($_ENV['WSO2IS_SKIP_SSL_VERIFY']) && $_ENV['WSO2IS_SKIP_SSL_VERIFY'])) {
            $http = $http->withoutVerifying();
        }

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

        $jsonData = $response->json();
        return $jsonData ?? [];
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
        if (!$this->users) {
            $this->users = new User($this);
        }

        return $this->users;
    }

    /**
     * Get Groups resource
     */
    public function groups(): Group
    {
        if (!$this->groups) {
            $this->groups = new Group($this);
        }

        return $this->groups;
    }

    /**
     * Get Applications resource
     */
    public function applications(): Application
    {
        if (!$this->applications) {
            $this->applications = new Application($this);
        }

        return $this->applications;
    }

    /**
     * Get authentication method being used (always OAuth2 Bearer)
     */
    public function getAuthMethod(): string
    {
        return 'oauth2_bearer';
    }
}
