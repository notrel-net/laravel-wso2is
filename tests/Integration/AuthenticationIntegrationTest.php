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

    public function test_client_uses_oidc_compliant_authentication()
    {
        // Verify the client always uses OAuth2 Bearer authentication (OIDC compliant)
        $authMethod = $this->client->getAuthMethod();

        $this->assertEquals('oauth2_bearer', $authMethod, 'Client should use OIDC compliant OAuth2 Bearer token authentication');
    }

    public function test_oauth_login_request_generates_valid_url()
    {
        // Set up required configuration for the login request
        config(['services.wso2is.base_url' => $_ENV['WSO2IS_BASE_URL']]);
        config(['services.wso2is.client_id' => $_ENV['WSO2IS_CLIENT_ID']]);
        config(['services.wso2is.client_secret' => $_ENV['WSO2IS_CLIENT_SECRET']]);
        config(['services.wso2is.redirect_uri' => 'https://example.com/auth/callback']);

        $loginRequest = new \Notrel\LaravelWso2is\Http\Requests\Wso2isLoginRequest();
        $redirectUrl = $loginRequest->getRedirectUrl();

        // Check the URL contains expected OAuth parameters
        $this->assertStringContainsString('oauth2/authorize', $redirectUrl);
        $this->assertStringContainsString('response_type=code', $redirectUrl);
        $this->assertStringContainsString('client_id=', $redirectUrl);
        $this->assertStringContainsString('redirect_uri=', $redirectUrl);
        $this->assertStringContainsString('scope=', $redirectUrl);
        $this->assertStringContainsString('state=', $redirectUrl);
        $this->assertStringContainsString('nonce=', $redirectUrl);
    }
}
