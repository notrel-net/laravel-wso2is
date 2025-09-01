<?php

namespace Tests\Integration;

use Notrel\LaravelWso2is\Http\Client;
use Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected Client $client;
    protected array $createdUsers = [];
    protected array $createdGroups = [];
    protected array $createdApplications = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Skip integration tests if no WSO2IS instance is configured
        if (!$this->hasWso2isConfig()) {
            $this->markTestSkipped('WSO2IS integration tests require real instance configuration');
        }

        $this->client = new Client(
            baseUrl: $_ENV['WSO2IS_BASE_URL'],
            clientId: $_ENV['WSO2IS_CLIENT_ID'],
            clientSecret: $_ENV['WSO2IS_CLIENT_SECRET']
        );
    }

    protected function tearDown(): void
    {
        // Clean up created resources
        $this->cleanupCreatedResources();
        parent::tearDown();
    }

    protected function hasWso2isConfig(): bool
    {
        return !empty($_ENV['WSO2IS_BASE_URL']) &&
            !empty($_ENV['WSO2IS_CLIENT_ID']) &&
            !empty($_ENV['WSO2IS_CLIENT_SECRET']);
    }

    protected function cleanupCreatedResources(): void
    {
        // Clean up users
        foreach ($this->createdUsers as $userId) {
            try {
                $this->client->users()->delete($userId);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }

        // Clean up groups
        foreach ($this->createdGroups as $groupId) {
            try {
                $this->client->groups()->delete($groupId);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }

        // Clean up applications
        foreach ($this->createdApplications as $appId) {
            try {
                $this->client->applications()->delete($appId);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }

    protected function createTestUser(array $userData = []): array
    {
        $defaultData = [
            'userName' => 'test-user-' . uniqid(),
            'name' => [
                'givenName' => 'Test',
                'familyName' => 'User'
            ],
            'emails' => [
                [
                    'value' => 'test-' . uniqid() . '@example.com',
                    'primary' => true
                ]
            ],
            'password' => 'TestPassword123!'
        ];

        $userData = array_merge($defaultData, $userData);
        $user = $this->client->users()->create($userData);
        $this->createdUsers[] = $user['id'];

        return $user;
    }

    protected function createTestGroup(array $groupData = []): array
    {
        $defaultData = [
            'displayName' => 'test-group-' . uniqid(),
            'members' => []
        ];

        $groupData = array_merge($defaultData, $groupData);
        $group = $this->client->groups()->create($groupData);
        $this->createdGroups[] = $group['id'];

        return $group;
    }
}
