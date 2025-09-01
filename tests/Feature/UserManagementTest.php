<?php

use Donmbelembe\LaravelWso2is\Http\Client;
use Donmbelembe\LaravelWso2is\Resources\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new Client(
        'https://test.wso2is.com',
        'test-client-id',
        'test-client-secret'
    );
});

it('makes authenticated requests with bearer token', function () {
    Http::fake([
        '*/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ]),
        '*/scim2/Users' => Http::response([
            'totalResults' => 1,
            'Resources' => [
                [
                    'id' => 'user123',
                    'userName' => 'john.doe',
                    'emails' => [['value' => 'john@example.com']]
                ]
            ]
        ])
    ]);

    $users = $this->client->users()->list();

    expect($users)
        ->toHaveKey('totalResults', 1)
        ->toHaveKey('Resources')
        ->and($users['Resources'])
        ->toHaveCount(1)
        ->and($users['Resources'][0])
        ->toHaveKey('id', 'user123')
        ->toHaveKey('userName', 'john.doe');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-access-token');
    });
});

it('handles user creation with proper data structure', function () {
    Http::fake([
        '*/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ]),
        '*/scim2/Users' => Http::response([
            'id' => 'new-user-123',
            'userName' => 'jane.doe',
            'emails' => [['value' => 'jane@example.com', 'primary' => true]]
        ], 201)
    ]);

    $userData = [
        'userName' => 'jane.doe',
        'name' => [
            'givenName' => 'Jane',
            'familyName' => 'Doe'
        ],
        'emails' => [
            [
                'value' => 'jane@example.com',
                'primary' => true
            ]
        ]
    ];

    $user = $this->client->users()->create($userData);

    expect($user)
        ->toHaveKey('id', 'new-user-123')
        ->toHaveKey('userName', 'jane.doe')
        ->toHaveKey('emails')
        ->and($user['emails'][0])
        ->toHaveKey('value', 'jane@example.com')
        ->toHaveKey('primary', true);
});

it('supports user filtering by username', function () {
    Http::fake([
        '*/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ]),
        '*/scim2/Users*' => Http::response([
            'totalResults' => 1,
            'Resources' => [
                [
                    'id' => 'user123',
                    'userName' => 'john.doe',
                    'emails' => [['value' => 'john@example.com']]
                ]
            ]
        ])
    ]);

    $user = $this->client->users()->getByUsername('john.doe');

    expect($user)
        ->toHaveKey('id', 'user123')
        ->toHaveKey('userName', 'john.doe');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'filter=userName%20eq%20%22john.doe%22');
    });
});
