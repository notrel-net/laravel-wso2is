<?php

namespace Tests\Integration;

class AuthenticationIntegrationTest extends IntegrationTestCase
{
    public function test_it_can_get_access_token()
    {
        $token = $this->client->getAccessToken();

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    public function test_it_can_make_authenticated_requests()
    {
        // This test verifies that the client can authenticate and make API calls
        $users = $this->client->users()->list();

        $this->assertIsArray($users);
        $this->assertArrayHasKey('Resources', $users);
    }

    public function test_oauth_login_request_generates_valid_url()
    {
        $loginRequest = new \Notrel\LaravelWso2is\Http\Requests\Wso2isLoginRequest();
        $redirectUrl = $loginRequest->getRedirectUrl();

        $this->assertStringContainsString($_ENV['WSO2IS_BASE_URL'], $redirectUrl);
        $this->assertStringContainsString('oauth2/authorize', $redirectUrl);
        $this->assertStringContainsString('client_id=' . $_ENV['WSO2IS_CLIENT_ID'], $redirectUrl);
        $this->assertStringContainsString('response_type=code', $redirectUrl);
    }
}
