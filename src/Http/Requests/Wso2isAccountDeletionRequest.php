<?php

namespace Notrel\LaravelWso2is\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Notrel\LaravelWso2is\Wso2is;
use Symfony\Component\HttpFoundation\Response;

class Wso2isAccountDeletionRequest extends FormRequest
{
    /**
     * Delete the user's account and perform cleanup.
     */
    public function deleteAccount(?callable $deleteUsing = null, ?string $redirectTo = null): Response
    {
        Wso2is::configure();

        $deleteUsing ??= $this->deleteUsing(...);

        $user = Auth::user();
        $accessToken = $this->session()->get('wso2is_access_token');

        if ($user && $accessToken) {
            // Delete user from WSO2IS if we have access token
            $this->deleteUserFromWso2is($user, $accessToken);

            // Delete user from local database
            $deleteUsing($user);
        }

        // Logout and clear session
        Auth::guard('web')->logout();
        $this->session()->invalidate();
        $this->session()->regenerateToken();

        return $this->redirect($redirectTo ?? '/');
    }

    /**
     * Default implementation to delete user from local database.
     */
    protected function deleteUsing($user): void
    {
        if (method_exists($user, 'delete')) {
            $user->delete();
        }
    }

    /**
     * Delete user from WSO2IS via SCIM2 API.
     */
    protected function deleteUserFromWso2is($user, string $accessToken): void
    {
        try {
            // Get WSO2IS user ID from the user model
            $wso2isUserId = $user->wso2is_id ?? null;

            if (!$wso2isUserId) {
                // Try to find user by email if no wso2is_id stored
                $client = app('wso2is');
                $users = $client->users()->list(['filter' => "emails eq \"{$user->email}\""]);

                if (!empty($users['Resources'])) {
                    $wso2isUserId = $users['Resources'][0]['id'];
                }
            }

            if ($wso2isUserId) {
                $client = app('wso2is');
                $client->users()->delete($wso2isUserId);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the deletion process
            // The local user will still be deleted
            report($e);
        }
    }

    /**
     * Redirect to the specified URL or default.
     */
    protected function redirect(?string $redirectTo = null): Response
    {
        $to = $redirectTo ?? '/';

        return class_exists(Inertia::class)
            ? Inertia::location($to)
            : redirect($to);
    }
}
