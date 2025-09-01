<?php

namespace Donmbelembe\LaravelWso2is\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Donmbelembe\LaravelWso2is\Wso2is;
use Symfony\Component\HttpFoundation\Response;

class Wso2isLoginRequest extends FormRequest
{
    /**
     * Redirect the user to WSO2IS for authentication.
     *
     * @param  array{
     *     prompt?: 'login'|'consent'|'select_account'|'none',
     *     loginHint?: string,
     *     domainHint?: string,
     *     maxAge?: int,
     *     acrValues?: string
     * }  $options
     */
    public function redirect(array $options = []): Response
    {
        Wso2is::configure();

        $state = [
            'state' => Str::random(32),
            'previous_url' => base64_encode(URL::previous()),
            'nonce' => Str::random(32),
        ];

        $params = [
            'response_type' => 'code',
            'client_id' => config('services.wso2is.client_id'),
            'redirect_uri' => config('services.wso2is.redirect_uri'),
            'scope' => implode(' ', config('services.wso2is.scopes', ['openid', 'profile', 'email'])),
            'state' => json_encode($state),
            'nonce' => $state['nonce'],
        ];

        // Add optional parameters
        if (isset($options['prompt'])) {
            $params['prompt'] = $options['prompt'];
        }

        if (isset($options['loginHint'])) {
            $params['login_hint'] = $options['loginHint'];
        }

        if (isset($options['domainHint'])) {
            $params['domain_hint'] = $options['domainHint'];
        }

        if (isset($options['maxAge'])) {
            $params['max_age'] = $options['maxAge'];
        }

        if (isset($options['acrValues'])) {
            $params['acr_values'] = $options['acrValues'];
        }

        $url = config('services.wso2is.base_url') . '/oauth2/authorize?' . http_build_query($params);

        $this->session()->put('wso2is_state', json_encode($state));

        return class_exists(Inertia::class)
            ? Inertia::location($url)
            : redirect($url);
    }
}
