<?php

namespace Notrel\LaravelWso2is\Resources;

use Notrel\LaravelWso2is\Http\Client;

class User
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List all users
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/scim2/Users', $filters);
    }

    /**
     * Get a specific user by ID
     */
    public function get(string $userId): array
    {
        return $this->client->get("/scim2/Users/{$userId}");
    }

    /**
     * Create a new user
     */
    public function create(array $userData): array
    {
        return $this->client->post('/scim2/Users', $userData);
    }

    /**
     * Update an existing user
     */
    public function update(string $userId, array $userData): array
    {
        return $this->client->put("/scim2/Users/{$userId}", $userData);
    }

    /**
     * Delete a user
     */
    public function delete(string $userId): array
    {
        return $this->client->delete("/scim2/Users/{$userId}");
    }

    /**
     * Get user by username
     */
    public function getByUsername(string $username): array
    {
        return $this->findByFilter("userName eq \"{$username}\"", "username '{$username}'");
    }

    /**
     * Get user by email
     */
    public function getByEmail(string $email): array
    {
        return $this->findByFilter("emails eq \"{$email}\"", "email '{$email}'");
    }

    /**
     * Find resource by filter
     */
    protected function findByFilter(string $filter, string $description): array
    {
        $response = $this->client->get('/scim2/Users', ['filter' => $filter]);

        if (empty($response['Resources'])) {
            throw new \Exception("User with {$description} not found");
        }

        return $response['Resources'][0];
    }
}
