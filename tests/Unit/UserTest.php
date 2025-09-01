<?php

use Notrel\LaravelWso2is\User;

it('creates user with all properties', function () {
    $user = new User(
        id: 'user123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        username: 'johndoe',
        groups: ['admin', 'developers'],
        roles: ['user', 'admin'],
        avatar: 'https://example.com/avatar.jpg',
        organizationId: 'org123'
    );

    expect($user->id)->toBe('user123');
    expect($user->firstName)->toBe('John');
    expect($user->lastName)->toBe('Doe');
    expect($user->email)->toBe('john.doe@example.com');
    expect($user->username)->toBe('johndoe');
    expect($user->groups)->toBe(['admin', 'developers']);
    expect($user->roles)->toBe(['user', 'admin']);
    expect($user->avatar)->toBe('https://example.com/avatar.jpg');
    expect($user->organizationId)->toBe('org123');
});

it('generates full name correctly', function () {
    $user = new User(
        id: 'user123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com'
    );

    expect($user->getFullName())->toBe('John Doe');
});

it('handles missing first or last name in full name', function () {
    $user1 = new User(
        id: 'user123',
        firstName: 'John',
        lastName: null,
        email: 'john@example.com'
    );

    $user2 = new User(
        id: 'user123',
        firstName: null,
        lastName: 'Doe',
        email: 'doe@example.com'
    );

    expect($user1->getFullName())->toBe('John');
    expect($user2->getFullName())->toBe('Doe');
});

it('checks role membership correctly', function () {
    $user = new User(
        id: 'user123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        roles: ['user', 'admin', 'editor']
    );

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('user'))->toBeTrue();
    expect($user->hasRole('superuser'))->toBeFalse();
});

it('checks group membership correctly', function () {
    $user = new User(
        id: 'user123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        groups: ['developers', 'admins', 'qa']
    );

    expect($user->inGroup('developers'))->toBeTrue();
    expect($user->inGroup('admins'))->toBeTrue();
    expect($user->inGroup('marketing'))->toBeFalse();
});

it('checks any role membership correctly', function () {
    $user = new User(
        id: 'user123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        roles: ['user', 'editor']
    );

    expect($user->hasAnyRole(['admin', 'editor']))->toBeTrue();
    expect($user->hasAnyRole(['admin', 'superuser']))->toBeFalse();
    expect($user->hasAnyRole(['user']))->toBeTrue();
});

it('checks any group membership correctly', function () {
    $user = new User(
        id: 'user123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        groups: ['developers', 'qa']
    );

    expect($user->inAnyGroup(['admins', 'developers']))->toBeTrue();
    expect($user->inAnyGroup(['admins', 'marketing']))->toBeFalse();
    expect($user->inAnyGroup(['qa']))->toBeTrue();
});

it('converts user to array correctly', function () {
    $user = new User(
        id: 'user123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john.doe@example.com',
        username: 'johndoe',
        groups: ['developers'],
        roles: ['user'],
        avatar: 'https://example.com/avatar.jpg',
        organizationId: 'org123'
    );

    $array = $user->toArray();

    expect($array)->toBe([
        'id' => 'user123',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'email' => 'john.doe@example.com',
        'username' => 'johndoe',
        'groups' => ['developers'],
        'roles' => ['user'],
        'avatar' => 'https://example.com/avatar.jpg',
        'organizationId' => 'org123',
        'fullName' => 'John Doe',
    ]);
});
