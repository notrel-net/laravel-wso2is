<?php

namespace Notrel\LaravelWso2is\Http\Requests;

use App\Models\User as AppUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Notrel\LaravelWso2is\User;
use Notrel\LaravelWso2is\Wso2is;
use Symfony\Component\HttpFoundation\Response;

class Wso2isAuthenticationRequest extends FormRequest
{
    /**
     * Authenticate the user with the authorization code.
     */
    public function authenticate(?callable $findUsing = null, ?callable $createUsing = null, ?callable $updateUsing = null): mixed
    {
        Wso2is::configure();

        $this->ensureStateIsValid();

        $findUsing ??= $this->findUsing(...);
        $createUsing ??= $this->createUsing(...);
        $updateUsing ??= $this->updateUsing(...);

        // Exchange authorization code for tokens
        $tokenResponse = Http::asForm()->post(config('services.wso2is.base_url') . '/oauth2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.wso2is.client_id'),
            'client_secret' => config('services.wso2is.client_secret'),
            'code' => $this->query('code'),
            'redirect_uri' => config('services.wso2is.redirect_uri'),
            'scope' => implode(' ', config('services.wso2is.scopes', ['openid', 'profile', 'email'])),
        ]);

        if (! $tokenResponse->successful()) {
            abort(400, 'Failed to exchange authorization code for tokens.');
        }

        $tokens = $tokenResponse->json();
        $accessToken = $tokens['access_token'];
        $refreshToken = $tokens['refresh_token'] ?? null;

        // Get user information
        $user = Wso2is::getUserFromToken($accessToken);

        if (! $user) {
            abort(400, 'Failed to retrieve user information.');
        }

        // Require email for authentication
        if (! $user->email) {
            abort(400, 'User account must have an email address to authenticate.');
        }

        $existingUser = $findUsing($user);

        if (! $existingUser) {
            $existingUser = $createUsing($user);
            event(new Registered($existingUser));
        } elseif (! is_null($updateUsing)) {
            $existingUser = $updateUsing($existingUser, $user);
        }

        Auth::guard('web')->login($existingUser);

        $this->session()->put('wso2is_access_token', $accessToken);
        if ($refreshToken) {
            $this->session()->put('wso2is_refresh_token', $refreshToken);
        }

        $this->session()->regenerate();
        $this->session()->forget('wso2is_state');

        return $existingUser;
    }

    /**
     * Find the user with the given WSO2IS ID.
     */
    protected function findUsing(User $user): ?AppUser
    {
        /** @phpstan-ignore class.notFound */
        return AppUser::where('wso2is_id', $user->id)->first();
    }

    /**
     * Create a user from the given WSO2IS user.
     */
    protected function createUsing(User $user): AppUser
    {
        /** @phpstan-ignore class.notFound */
        return AppUser::create([
            'name' => $user->getFullName() ?: $user->username ?: $user->id,
            'email' => $user->email, // ?: $user->username . '@unknown.local',
            'email_verified_at' => now(),
            'wso2is_id' => $user->id,
            'avatar' => $user->avatar ?? '',
        ]);
    }

    /**
     * Update a user from the given WSO2IS user.
     */
    protected function updateUsing(AppUser $user, User $userFromWso2is): AppUser
    {
        return tap($user)->update([
            'name' => $userFromWso2is->getFullName() ?: $userFromWso2is->username ?: $userFromWso2is->id,
            'avatar' => $userFromWso2is->avatar ?? '',
        ]);
    }

    /**
     * Redirect the user to the previous URL or a default URL if no previous URL is available.
     */
    public function redirect(string $default = '/'): Response
    {
        $previousUrl = rtrim(base64_decode($this->sessionState()['previous_url'] ?? '/')) ?: null;

        $to = ! is_null($previousUrl) && $previousUrl !== URL::to('/')
            ? $previousUrl
            : $default;

        return class_exists(Inertia::class)
            ? Inertia::location($to)
            : redirect($to);
    }

    /**
     * Ensure the request state is valid.
     */
    protected function ensureStateIsValid(): void
    {
        $state = json_decode($this->query('state'), true)['state'] ?? false;

        if ($state !== ($this->sessionState()['state'] ?? false)) {
            abort(403, 'Invalid state parameter.');
        }
    }

    /**
     * Get the session state.
     */
    protected function sessionState(): array
    {
        return json_decode($this->session()->get('wso2is_state'), true) ?: [];
    }
}
