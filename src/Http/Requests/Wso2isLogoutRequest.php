<?php

namespace Notrel\LaravelWso2is\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Notrel\LaravelWso2is\Wso2is;
use Symfony\Component\HttpFoundation\Response;

class Wso2isLogoutRequest extends FormRequest
{
    /**
     * Redirect the user to WSO2IS for logout.
     */
    public function logout(?string $redirectTo = null): Response
    {
        $accessToken = $this->session()->get('wso2is_access_token');

        Auth::guard('web')->logout();

        $this->session()->invalidate();
        $this->session()->regenerateToken();

        // If we have an access token, perform SSO logout
        if ($accessToken) {
            Wso2is::configure();

            $logoutUrl = $this->buildLogoutUrl($redirectTo);

            return class_exists(Inertia::class)
                ? Inertia::location($logoutUrl)
                : redirect($logoutUrl);
        }

        return redirect($redirectTo ?? '/');
    }

    /**
     * Get the logout URL for WSO2IS without performing logout.
     * Useful for API responses, AJAX calls, or custom logout handling.
     */
    public function getRedirectUrl(?string $redirectTo = null): string
    {
        Wso2is::configure();
        return $this->buildLogoutUrl($redirectTo);
    }

    /**
     * Build the logout URL for WSO2IS.
     */
    protected function buildLogoutUrl(?string $redirectTo = null): string
    {
        $params = [];

        if ($redirectTo) {
            $params['post_logout_redirect_uri'] = url($redirectTo);
        }

        $logoutEndpoint = config('services.wso2is.base_url') . '/oidc/logout';
        return $logoutEndpoint . (empty($params) ? '' : '?' . http_build_query($params));
    }
}
