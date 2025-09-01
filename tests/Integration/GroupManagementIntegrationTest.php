<?php

namespace Tests\Integration;

class GroupManagementIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip if no management scopes are configured
        if (!$this->hasManagementScopes()) {
            $this->markTestSkipped('GroupManagementIntegrationTest requires OAuth management scopes to be configured in WSO2IS');
        }
    }

    /**
     * Check if management scopes are available
     */
    protected function hasManagementScopes(): bool
    {
        try {
            // Try to list groups to check if we have management permissions
            $this->client->groups()->list();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function test_it_can_create_and_retrieve_group()
    {
        $groupData = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'],
            'displayName' => 'IntegrationTestGroup' . time(), // WSO2IS 7.1 prefers no dashes
            'members' => []
        ];

        $createdGroup = $this->client->groups()->create($groupData);
        $this->createdGroups[] = $createdGroup['id'];

        // Verify group was created
        $this->assertNotEmpty($createdGroup['id']);
        $this->assertEquals($groupData['displayName'], $createdGroup['displayName']);

        // Retrieve the group
        $retrievedGroup = $this->client->groups()->get($createdGroup['id']);
        $this->assertEquals($createdGroup['id'], $retrievedGroup['id']);
        $this->assertEquals($createdGroup['displayName'], $retrievedGroup['displayName']);
    }

    public function test_it_can_add_and_remove_user_from_group()
    {
        $user = $this->createTestUser();
        $group = $this->createTestGroup();

        // Add user to group
        $updatedGroup = $this->client->groups()->addUser($group['id'], $user['id']);

        // Verify user was added
        $members = $updatedGroup['members'] ?? [];
        $userInGroup = false;
        foreach ($members as $member) {
            if ($member['value'] === $user['id']) {
                $userInGroup = true;
                break;
            }
        }
        $this->assertTrue($userInGroup, 'User should be added to group');

        // Remove user from group
        $updatedGroup = $this->client->groups()->removeUser($group['id'], $user['id']);

        // Verify user was removed
        $members = $updatedGroup['members'] ?? [];
        $userInGroup = false;
        foreach ($members as $member) {
            if ($member['value'] === $user['id']) {
                $userInGroup = true;
                break;
            }
        }
        $this->assertFalse($userInGroup, 'User should be removed from group');
    }

    public function test_it_can_find_group_by_name()
    {
        $group = $this->createTestGroup();

        $foundGroup = $this->client->groups()->getByName($group['displayName']);
        $this->assertEquals($group['id'], $foundGroup['id']);
        $this->assertEquals($group['displayName'], $foundGroup['displayName']);
    }

    public function test_it_can_list_groups()
    {
        // Create a test group
        $group = $this->createTestGroup();

        // List groups
        $groups = $this->client->groups()->list();

        $this->assertIsArray($groups);
        $this->assertArrayHasKey('Resources', $groups);

        // Find our created group in the list
        $groupFound = false;
        foreach ($groups['Resources'] as $listedGroup) {
            if ($listedGroup['id'] === $group['id']) {
                $groupFound = true;
                break;
            }
        }
        $this->assertTrue($groupFound, 'Created group should be found in group list');
    }
}
