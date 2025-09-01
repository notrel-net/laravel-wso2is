<?php

namespace Donmbelembe\LaravelWso2is\Facades;

use Illuminate\Support\Facades\Facade;
use Donmbelembe\LaravelWso2is\Http\Client;

/**
 * @method static string getAccessToken()
 * @method static array request(string $method, string $endpoint, array $data = [])
 * @method static array get(string $endpoint, array $params = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint, array $data = [])
 *
 * @see \Donmbelembe\LaravelWso2is\Http\Client
 */
class Wso2is extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'wso2is';
    }
}
