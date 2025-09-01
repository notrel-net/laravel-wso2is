<?php

namespace Notrel\LaravelWso2is\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Notrel\LaravelWso2is\Wso2is;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ValidateSessionWithWso2is
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        Wso2is::configure();

        if (
            ! $request->session()->get('wso2is_access_token') ||
            ! $request->session()->get('wso2is_refresh_token')
        ) {
            return $this->logout($request);
        }

        try {
            [$accessToken, $refreshToken] = Wso2is::ensureAccessTokenIsValid(
                $request->session()->get('wso2is_access_token'),
                $request->session()->get('wso2is_refresh_token'),
            );

            $request->session()->put('wso2is_access_token', $accessToken);
            $request->session()->put('wso2is_refresh_token', $refreshToken);
        } catch (\Exception $e) {
            report($e);

            return $this->logout($request);
        }

        return $next($request);
    }

    /**
     * Log the user out of the application.
     */
    protected function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
