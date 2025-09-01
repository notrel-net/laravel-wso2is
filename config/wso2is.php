<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WSO2 Identity Server Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your WSO2 Identity Server instance.
    |
    */

    'base_url' => env('WSO2IS_BASE_URL', 'https://localhost:9443'),

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Client Credentials
    |--------------------------------------------------------------------------
    |
    | The OAuth2 client ID and secret for your application registered
    | in WSO2 Identity Server.
    |
    */

    'client_id' => env('WSO2IS_CLIENT_ID'),

    'client_secret' => env('WSO2IS_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Default Scopes
    |--------------------------------------------------------------------------
    |
    | The default scopes to request when obtaining an access token.
    |
    */

    'scopes' => [
        'openid',
        'profile',
        'email',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache settings for access tokens and other API responses.
    |
    */

    'cache' => [
        'token_ttl' => 3600, // 1 hour in seconds
        'prefix' => 'wso2is_',
    ],

];
