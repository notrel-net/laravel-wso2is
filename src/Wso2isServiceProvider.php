<?php

namespace Laravel\Wso2is;

use Illuminate\Support\ServiceProvider;
use Laravel\Wso2is\Http\Client;

class Wso2isServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/wso2is.php',
            'wso2is'
        );

        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                baseUrl: $app['config']['wso2is.base_url'],
                clientId: $app['config']['wso2is.client_id'],
                clientSecret: $app['config']['wso2is.client_secret']
            );
        });

        $this->app->alias(Client::class, 'wso2is');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/wso2is.php' => config_path('wso2is.php'),
            ], 'wso2is-config');
        }

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
