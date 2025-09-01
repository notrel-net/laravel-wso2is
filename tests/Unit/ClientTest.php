<?php

use Notrel\LaravelWso2is\Http\Client;
use Notrel\LaravelWso2is\Resources\User;
use Notrel\LaravelWso2is\Resources\Group;
use Notrel\LaravelWso2is\Resources\Application;
use Illuminate\Support\Facades\Http;

it('can create client instance', function () {
    $client = new Client(
        'https://test.wso2is.com',
        'test-client-id',
        'test-client-secret'
    );

    expect($client)->toBeInstanceOf(Client::class);
});

it('can get access token', function () {
    Http::fake([
        '*/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ])
    ]);

    $client = new Client(
        'https://test.wso2is.com',
        'test-client-id',
        'test-client-secret'
    );

    $token = $client->getAccessToken();

    expect($token)->toBe('test-access-token');
});

it('can access users resource', function () {
    $client = new Client(
        'https://test.wso2is.com',
        'test-client-id',
        'test-client-secret'
    );

    $users = $client->users();

    expect($users)->toBeInstanceOf(User::class);
});

it('can access groups resource', function () {
    $client = new Client(
        'https://test.wso2is.com',
        'test-client-id',
        'test-client-secret'
    );

    $groups = $client->groups();

    expect($groups)->toBeInstanceOf(Group::class);
});

it('can access applications resource', function () {
    $client = new Client(
        'https://test.wso2is.com',
        'test-client-id',
        'test-client-secret'
    );

    $applications = $client->applications();

    expect($applications)->toBeInstanceOf(Application::class);
});
