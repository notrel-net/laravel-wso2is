<?php

namespace Laravel\Wso2is\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Wso2is\Facades\Wso2is;

class CallbackController extends Controller
{
    /**
     * Handle the OAuth2 callback from WSO2IS
     */
    public function handle(Request $request)
    {
        $code = $request->get('code');
        $state = $request->get('state');

        if (!$code) {
            return response()->json(['error' => 'Authorization code not provided'], 400);
        }

        // Exchange authorization code for access token
        try {
            $tokenResponse = Wso2is::post('/oauth2/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('wso2is.callback'),
                'client_id' => config('wso2is.client_id'),
                'client_secret' => config('wso2is.client_secret'),
            ]);

            // Get user info using the access token
            $userInfo = Wso2is::get('/oauth2/userinfo', [
                'access_token' => $tokenResponse['access_token']
            ]);

            return response()->json([
                'user' => $userInfo,
                'token' => $tokenResponse
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
