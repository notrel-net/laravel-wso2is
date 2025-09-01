<?php

use Donmbelembe\LaravelWso2is\Http\Requests\Wso2isLoginRequest;
use Donmbelembe\LaravelWso2is\Http\Requests\Wso2isAuthenticationRequest;
use Donmbelembe\LaravelWso2is\Http\Requests\Wso2isLogoutRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;

beforeEach(function () {
    Config::set('wso2is.base_url', 'https://localhost:9443');
    Config::set('wso2is.client_id', 'test_client_id');
    Config::set('wso2is.client_secret', 'test_client_secret');
    Config::set('wso2is.redirect_uri', 'https://laravel.app/wso2is/callback');
    Config::set('wso2is.scopes', ['openid', 'profile', 'email']);
});

it('creates login request and redirects to WSO2IS', function () {
    $request = Wso2isLoginRequest::create('/', 'GET');
    $request->setLaravelSession(app('session.store'));

    $response = $request->redirect();

    expect($response)->toBeInstanceOf(RedirectResponse::class);

    $location = $response->headers->get('Location');
    expect($location)
        ->toContain('https://localhost:9443/oauth2/authorize')
        ->toContain('client_id=test_client_id')
        ->toContain('response_type=code')
        ->toContain('redirect_uri=' . urlencode('https://laravel.app/wso2is/callback'))
        ->toContain('scope=' . urlencode('openid profile email'))
        ->toContain('state=');
});

it('stores state in session during login', function () {
    $request = Wso2isLoginRequest::create('/', 'GET');
    $request->setLaravelSession(app('session.store'));

    $response = $request->redirect();

    $sessionState = Session::get('wso2is_state');
    expect($sessionState)->not->toBeNull();

    $decodedState = json_decode($sessionState, true);
    expect($decodedState)
        ->toHaveKey('state')
        ->toHaveKey('previous_url')
        ->toHaveKey('nonce')
        ->and($decodedState['state'])->toHaveLength(32)
        ->and($decodedState['nonce'])->toHaveLength(32);
});

it('supports login parameters', function () {
    $request = Wso2isLoginRequest::create('/', 'GET');
    $request->setLaravelSession(app('session.store'));

    $response = $request->redirect([
        'prompt' => 'login',
        'loginHint' => 'user@example.com',
        'domainHint' => 'example.com',
        'maxAge' => 3600,
    ]);

    $location = $response->headers->get('Location');
    expect($location)
        ->toContain('prompt=login')
        ->toContain('login_hint=' . urlencode('user@example.com'))
        ->toContain('domain_hint=example.com')
        ->toContain('max_age=3600');
});

it('creates logout request and redirects', function () {
    $request = Wso2isLogoutRequest::create('/', 'POST');
    $request->setLaravelSession(app('session.store'));

    // Test without access token (simple redirect)
    $response = $request->logout('/home');

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->headers->get('Location'))->toBe(url('/home'));
});
