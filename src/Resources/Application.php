<?php

namespace Notrel\LaravelWso2is\Resources;

use Notrel\LaravelWso2is\Http\Client;

class Application
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List all applications
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/api/server/v1/applications', $filters);
    }

    /**
     * Get a specific application by ID
     */
    public function get(string $applicationId): array
    {
        return $this->client->get("/api/server/v1/applications/{$applicationId}");
    }

    /**
     * Create a new application
     */
    public function create(array $applicationData): array
    {
        return $this->client->post('/api/server/v1/applications', $applicationData);
    }

    /**
     * Update an existing application
     */
    public function update(string $applicationId, array $applicationData): array
    {
        return $this->client->put("/api/server/v1/applications/{$applicationId}", $applicationData);
    }

    /**
     * Delete an application
     */
    public function delete(string $applicationId): array
    {
        return $this->client->delete("/api/server/v1/applications/{$applicationId}");
    }

    /**
     * Get application inbound configurations
     */
    public function getInboundConfig(string $applicationId): array
    {
        return $this->client->get("/api/server/v1/applications/{$applicationId}/inbound-protocols");
    }

    /**
     * Update OAuth2 inbound configuration
     */
    public function updateOAuth2Config(string $applicationId, array $oauthConfig): array
    {
        return $this->client->put("/api/server/v1/applications/{$applicationId}/inbound-protocols/oidc", $oauthConfig);
    }

    /**
     * Get OAuth2 configuration
     */
    public function getOAuth2Config(string $applicationId): array
    {
        return $this->client->get("/api/server/v1/applications/{$applicationId}/inbound-protocols/oidc");
    }

    /**
     * Regenerate client secret
     */
    public function regenerateClientSecret(string $applicationId): array
    {
        return $this->client->post("/api/server/v1/applications/{$applicationId}/inbound-protocols/oidc/regenerate-secret");
    }

    /**
     * Get application by name
     */
    public function getByName(string $name): array
    {
        $response = $this->client->get('/api/server/v1/applications', [
            'filter' => "name eq \"{$name}\""
        ]);

        if (empty($response['applications'])) {
            throw new \Exception("Application with name '{$name}' not found");
        }

        return $response['applications'][0];
    }
}
