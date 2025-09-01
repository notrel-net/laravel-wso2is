<?php

namespace Donmbelembe\LaravelWso2is;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Donmbelembe\LaravelWso2is\Http\Client;
use Donmbelembe\LaravelWso2is\Http\Middleware\ValidateSessionWithWso2is;

class Wso2isServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                baseUrl: $app['config']['services.wso2is.base_url'],
                clientId: $app['config']['services.wso2is.client_id'],
                clientSecret: $app['config']['services.wso2is.client_secret']
            );
        });

        $this->app->alias(Client::class, 'wso2is');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register middleware
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('wso2is.session', ValidateSessionWithWso2is::class);
    }
}
