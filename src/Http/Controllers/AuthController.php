<?php

namespace Donmbelembe\LaravelWso2is\Http\Controllers;

use Illuminate\Routing\Controller;
use Donmbelembe\LaravelWso2is\Http\Requests\Wso2isLoginRequest;
use Donmbelembe\LaravelWso2is\Http\Requests\Wso2isAuthenticationRequest;
use Donmbelembe\LaravelWso2is\Http\Requests\Wso2isLogoutRequest;

class AuthController extends Controller
{
    /**
     * Redirect to WSO2IS for authentication.
     */
    public function login(Wso2isLoginRequest $request)
    {
        return $request->redirect([
            'prompt' => 'login', // Force login prompt
        ]);
    }

    /**
     * Handle the OAuth2 callback from WSO2IS.
     */
    public function callback(Wso2isAuthenticationRequest $request)
    {
        $user = $request->authenticate();

        return $request->redirect('/dashboard');
    }

    /**
     * Logout the user and redirect to WSO2IS logout.
     */
    public function logout(Wso2isLogoutRequest $request)
    {
        return $request->logout('/');
    }
}
