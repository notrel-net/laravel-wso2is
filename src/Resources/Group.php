<?php

namespace Laravel\Wso2is\Resources;

use Laravel\Wso2is\Http\Client;

class Group
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * List all groups
     */
    public function list(array $filters = []): array
    {
        return $this->client->get('/scim2/Groups', $filters);
    }

    /**
     * Get a specific group by ID
     */
    public function get(string $groupId): array
    {
        return $this->client->get("/scim2/Groups/{$groupId}");
    }

    /**
     * Create a new group
     */
    public function create(array $groupData): array
    {
        return $this->client->post('/scim2/Groups', $groupData);
    }

    /**
     * Update an existing group
     */
    public function update(string $groupId, array $groupData): array
    {
        return $this->client->put("/scim2/Groups/{$groupId}", $groupData);
    }

    /**
     * Delete a group
     */
    public function delete(string $groupId): array
    {
        return $this->client->delete("/scim2/Groups/{$groupId}");
    }

    /**
     * Add user to group
     */
    public function addUser(string $groupId, string $userId): array
    {
        $group = $this->get($groupId);

        $members = $group['members'] ?? [];
        $members[] = [
            'value' => $userId,
            'display' => $userId
        ];

        return $this->update($groupId, [
            'members' => $members
        ]);
    }

    /**
     * Remove user from group
     */
    public function removeUser(string $groupId, string $userId): array
    {
        $group = $this->get($groupId);

        $members = array_filter($group['members'] ?? [], function ($member) use ($userId) {
            return $member['value'] !== $userId;
        });

        return $this->update($groupId, [
            'members' => array_values($members)
        ]);
    }

    /**
     * Get group by name
     */
    public function getByName(string $name): array
    {
        $response = $this->client->get('/scim2/Groups', [
            'filter' => "displayName eq \"{$name}\""
        ]);

        if (empty($response['Resources'])) {
            throw new \Exception("Group with name '{$name}' not found");
        }

        return $response['Resources'][0];
    }
}
