<?php

namespace Donmbelembe\LaravelWso2is;

class User
{
    public function __construct(
        public string $id,
        public ?string $firstName,
        public ?string $lastName,
        public string $email,
        public ?string $username = null,
        public array $groups = [],
        public array $roles = [],
        public ?string $avatar = null,
        public ?string $organizationId = null,
    ) {}

    /**
     * Get the user's full name.
     */
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     * Check if the user belongs to a specific group.
     */
    public function inGroup(string $group): bool
    {
        return in_array($group, $this->groups);
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return !empty(array_intersect($this->roles, $roles));
    }

    /**
     * Check if the user belongs to any of the given groups.
     */
    public function inAnyGroup(array $groups): bool
    {
        return !empty(array_intersect($this->groups, $groups));
    }

    /**
     * Get the user as an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'username' => $this->username,
            'groups' => $this->groups,
            'roles' => $this->roles,
            'avatar' => $this->avatar,
            'organizationId' => $this->organizationId,
            'fullName' => $this->getFullName(),
        ];
    }
}
