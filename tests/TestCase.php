<?php

namespace Tests;

use Notrel\LaravelWso2is\Wso2isServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            Wso2isServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Set up test configuration
        config()->set('services.wso2is.base_url', 'https://test.wso2is.com');
        config()->set('services.wso2is.client_id', 'test-client-id');
        config()->set('services.wso2is.client_secret', 'test-client-secret');
    }
}
