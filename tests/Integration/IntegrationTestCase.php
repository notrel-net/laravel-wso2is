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

        // Load environment variables from .env.integration if it exists
        $this->loadIntegrationEnv();

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

    protected function loadIntegrationEnv(): void
    {
        $envFile = __DIR__ . '/../../.env.integration';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parse KEY=VALUE format
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remove quotes if present
                    $value = trim($value, '"\'');

                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
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
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
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
            'password' => 'TestPassword123!',
            'active' => true
        ];

        $userData = array_merge($defaultData, $userData);
        $user = $this->client->users()->create($userData);
        $this->createdUsers[] = $user['id'];

        return $user;
    }

    protected function createTestGroup(array $groupData = []): array
    {
        $defaultData = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'],
            'displayName' => 'TestGroup' . uniqid(), // WSO2IS 7.1 doesn't like dashes in group names
            'members' => []
        ];

        $groupData = array_merge($defaultData, $groupData);
        $group = $this->client->groups()->create($groupData);
        $this->createdGroups[] = $group['id'];

        return $group;
    }
}
