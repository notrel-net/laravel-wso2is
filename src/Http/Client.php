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
     * Get OAuth2 access token from WSO2IS
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::asForm()->post($this->baseUrl . '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    /**
     * Make authenticated request to WSO2IS API
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getAccessToken();

        $http = Http::withToken($token);

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

        return $response->json();
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
}
