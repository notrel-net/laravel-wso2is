<?php

namespace Tests\Integration;

class UserManagementIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('UserManagementIntegrationTest is disabled - OAuth scopes need to be configured');
    }
    public function test_it_can_create_and_retrieve_user()
    {
        // Create a user
        $userData = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'userName' => 'integration-test-user-' . time(),
            'name' => [
                'givenName' => 'Integration',
                'familyName' => 'Test'
            ],
            'emails' => [
                [
                    'value' => 'integration-test-' . time() . '@example.com',
                    'primary' => true
                ]
            ],
            'password' => 'TestPassword123!',
            'active' => true
        ];

        $createdUser = $this->client->users()->create($userData);
        $this->createdUsers[] = $createdUser['id'];

        // Verify user was created
        $this->assertNotEmpty($createdUser['id']);
        $this->assertEquals($userData['userName'], $createdUser['userName']);
        $this->assertEquals($userData['name']['givenName'], $createdUser['name']['givenName']);

        // Retrieve the user
        $retrievedUser = $this->client->users()->get($createdUser['id']);
        $this->assertEquals($createdUser['id'], $retrievedUser['id']);
        $this->assertEquals($createdUser['userName'], $retrievedUser['userName']);
    }

    public function test_it_can_update_user()
    {
        $user = $this->createTestUser();

        // Update the user - WSO2IS 7.1 requires schemas and userName in update operations
        $updateData = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'userName' => $user['userName'], // Required in WSO2IS 7.1 updates
            'name' => [
                'givenName' => 'Updated',
                'familyName' => 'Name'
            ]
        ];

        $updatedUser = $this->client->users()->update($user['id'], $updateData);
        $this->assertEquals('Updated', $updatedUser['name']['givenName']);
        $this->assertEquals('Name', $updatedUser['name']['familyName']);
    }

    public function test_it_can_find_user_by_username()
    {
        $user = $this->createTestUser();

        $foundUser = $this->client->users()->getByUsername($user['userName']);
        $this->assertEquals($user['id'], $foundUser['id']);
        $this->assertEquals($user['userName'], $foundUser['userName']);
    }

    public function test_it_can_list_users()
    {
        // Create a test user
        $user = $this->createTestUser();

        // List users
        $users = $this->client->users()->list();

        $this->assertIsArray($users);
        $this->assertArrayHasKey('Resources', $users);
        $this->assertGreaterThan(0, count($users['Resources']));

        // Find our created user in the list
        $userFound = false;
        foreach ($users['Resources'] as $listedUser) {
            if ($listedUser['id'] === $user['id']) {
                $userFound = true;
                break;
            }
        }
        $this->assertTrue($userFound, 'Created user should be found in user list');
    }

    public function test_it_can_delete_user()
    {
        $user = $this->createTestUser();

        // Delete the user
        $this->client->users()->delete($user['id']);

        // Remove from cleanup list since we manually deleted
        $this->createdUsers = array_filter($this->createdUsers, function ($id) use ($user) {
            return $id !== $user['id'];
        });

        // Try to retrieve the deleted user - should throw exception
        $this->expectException(\Exception::class);
        $this->client->users()->get($user['id']);
    }
}
