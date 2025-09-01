# Laravel WSO2 Identity Server

A Laravel package for integrating with WSO2 Identity Server, providing OAuth2 authentication, user management, SCIM2 API access, and convenient middleware for session management.

## Features

- 🔐 **OAuth2/OIDC Authentication** - Complete OIDC flow with login, callback, and logout
- 👥 **User Management** - SCIM2 API for user CRUD operations
- 👪 **Group Management** - SCIM2 API for group operations  
- 🏢 **Application Management** - WSO2IS REST API for application configuration
- 🛡️ **Session Middleware** - Automatic token validation and refresh
- 📝 **Request Classes** - Laravel-style request classes for clean authentication flows
- ✅ **Pest Testing** - Comprehensive test suite with Pest framework

## Installation

You can install the package via composer:

```bash
composer require notrel/laravel-wso2is
```

The package will automatically register its service provider.

### Configuration

Add your WSO2IS configuration to your `config/services.php` file:

```php
'wso2is' => [
    'base_url' => env('WSO2IS_BASE_URL', 'https://localhost:9443'),
    'client_id' => env('WSO2IS_CLIENT_ID'),
    'client_secret' => env('WSO2IS_CLIENT_SECRET'),
    'redirect_uri' => env('WSO2IS_REDIRECT_URI'),
    'scopes' => env('WSO2IS_SCOPES', 'openid,profile,email'),
    'scim_username' => env('WSO2IS_SCIM_USERNAME'),
    'scim_password' => env('WSO2IS_SCIM_PASSWORD'),
],
```

Then update your `.env` file:

```env
WSO2IS_BASE_URL=https://your-wso2is-instance.com
WSO2IS_CLIENT_ID=your-client-id
WSO2IS_CLIENT_SECRET=your-client-secret
WSO2IS_REDIRECT_URI=https://your-app.com/auth/callback
WSO2IS_SCOPES="openid,profile,email"
WSO2IS_SCIM_USERNAME=your-scim-username
WSO2IS_SCIM_PASSWORD=your-scim-password
```

## Usage

### Authentication with Request Classes (Recommended)

The package provides convenient request classes similar to Laravel WorkOS. You create the routes and controllers in your application:

#### 1. Create Your Authentication Routes

```php
// routes/web.php
use Notrel\LaravelWso2is\Http\Requests\Wso2isLoginRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isAuthenticationRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isLogoutRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isAccountDeletionRequest;

Route::get('/auth/login', function (Wso2isLoginRequest $request) {
    return $request->redirect([
        'prompt' => 'login', // Optional: force login prompt
        'loginHint' => 'user@example.com', // Optional: pre-fill username
        'domainHint' => 'example.com', // Optional: domain hint
    ]);
})->name('auth.login');

Route::get('/auth/callback', function (Wso2isAuthenticationRequest $request) {
    $user = $request->authenticate();
    return $request->redirect('/dashboard');
})->name('auth.callback');

Route::post('/auth/logout', function (Wso2isLogoutRequest $request) {
    return $request->logout('/'); // Optional: redirect URL after logout
})->name('auth.logout');

Route::delete('/auth/delete-account', function (Wso2isAccountDeletionRequest $request) {
    return $request->deleteAccount(redirectTo: '/'); // Optional: redirect URL after deletion
})->name('auth.delete-account')->middleware('auth');
```

#### 2. Or Create a Controller

```php
// app/Http/Controllers/AuthController.php
<?php

namespace App\Http\Controllers;

use Notrel\LaravelWso2is\Http\Requests\Wso2isLoginRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isAuthenticationRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isLogoutRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isAccountDeletionRequest;

class AuthController extends Controller
{
    public function login(Wso2isLoginRequest $request)
    {
        return $request->redirect(['prompt' => 'login']);
    }

    public function callback(Wso2isAuthenticationRequest $request)
    {
        $user = $request->authenticate(
            // Optional: custom user finder
            findUsing: fn($wso2isUser) => User::where('wso2is_id', $wso2isUser->id)->first(),
            
            // Optional: custom user creator
            createUsing: fn($wso2isUser) => User::create([
                'name' => $wso2isUser->getFullName(),
                'email' => $wso2isUser->email,
                'wso2is_id' => $wso2isUser->id,
            ]),
            
            // Optional: custom user updater
            updateUsing: fn($user, $wso2isUser) => $user->update([
                'name' => $wso2isUser->getFullName(),
            ])
        );
        
        return $request->redirect('/dashboard');
    }

    public function logout(Wso2isLogoutRequest $request)
    {
        return $request->logout('/');
    }

    public function deleteAccount(Wso2isAccountDeletionRequest $request)
    {
        return $request->deleteAccount(
            // Optional: custom user deletion
            deleteUsing: fn($user) => $user->delete(),
            redirectTo: '/'
        );
    }
}
```

#### 3. Update Your Configuration

Make sure your redirect URI points to your callback route:

```env
WSO2IS_REDIRECT_URI=https://your-app.com/auth/callback
```

### Session Middleware

Protect your routes with automatic token validation and refresh by adding the middleware to your routes:

```php
Route::middleware('wso2is.session')->group(function () {
    Route::get('/dashboard', function () {
        // User is guaranteed to have valid WSO2IS session
        return view('dashboard');
    });
    
    Route::get('/profile', function () {
        // Access tokens are automatically refreshed if needed
        return view('profile');
    });
});
```

### User Model

The package includes a rich User model with role and group checking:

```php
use Notrel\LaravelWso2is\Wso2is;

$user = Wso2is::getUserFromToken($accessToken);

// User properties
echo $user->id;
echo $user->email;
echo $user->getFullName();
echo $user->username;

// Role checking
if ($user->hasRole('admin')) {
    // User has admin role
}

if ($user->hasAnyRole(['admin', 'moderator'])) {
    // User has at least one of these roles
}

// Group checking
if ($user->inGroup('developers')) {
    // User is in developers group
}

if ($user->inAnyGroup(['qa', 'testers'])) {
    // User is in at least one of these groups
}

// Convert to array
$userData = $user->toArray();
```

### Using the HTTP Client (Advanced)

```php
use Notrel\LaravelWso2is\Http\Client;

// Create a client instance
$client = new Client(
    baseUrl: config('services.wso2is.base_url'),
    clientId: config('services.wso2is.client_id'),
    clientSecret: config('services.wso2is.client_secret')
);

// Get access token
$token = $client->getAccessToken();

// Make raw API requests
$users = $client->get('/scim2/Users');
```

### User Management

```php
use Notrel\LaravelWso2is\Http\Client;

// Create a client instance
$client = new Client(
    baseUrl: config('services.wso2is.base_url'),
    clientId: config('services.wso2is.client_id'),
    clientSecret: config('services.wso2is.client_secret')
);

// List all users
$users = $client->users()->list();

// Get a specific user
$user = $client->users()->get('user-id');

// Get user by username
$user = $client->users()->getByUsername('john.doe');

// Get user by email
$user = $client->users()->getByEmail('john@example.com');

// Create a new user
$newUser = $client->users()->create([
    'userName' => 'john.doe',
    'name' => [
        'givenName' => 'John',
        'familyName' => 'Doe'
    ],
    'emails' => [
        [
            'value' => 'john@example.com',
            'primary' => true
        ]
    ],
    'password' => 'securePassword123'
]);

// Update a user
$updatedUser = $client->users()->update('user-id', [
    'name' => [
        'givenName' => 'John',
        'familyName' => 'Smith'
    ]
]);

// Delete a user
$client->users()->delete('user-id');
```

### Group Management

```php
use Notrel\LaravelWso2is\Http\Client;

// Create a client instance
$client = new Client(
    baseUrl: config('services.wso2is.base_url'),
    clientId: config('services.wso2is.client_id'),
    clientSecret: config('services.wso2is.client_secret')
);

// List all groups
$groups = $client->groups()->list();

// Get a specific group
$group = $client->groups()->get('group-id');

// Get group by name
$group = $client->groups()->getByName('Administrators');

// Create a new group
$newGroup = $client->groups()->create([
    'displayName' => 'New Group',
    'members' => [
        [
            'value' => 'user-id-1',
            'display' => 'user-id-1'
        ]
    ]
]);

// Add user to group
$client->groups()->addUser('group-id', 'user-id');

// Remove user from group
$client->groups()->removeUser('group-id', 'user-id');

// Delete a group
$client->groups()->delete('group-id');
```

### Application Management

```php
use Notrel\LaravelWso2is\Http\Client;

// Create a client instance
$client = new Client(
    baseUrl: config('services.wso2is.base_url'),
    clientId: config('services.wso2is.client_id'),
    clientSecret: config('services.wso2is.client_secret')
);

// List all applications
$applications = $client->applications()->list();

// Get a specific application
$app = $client->applications()->get('app-id');

// Get application by name
$app = $client->applications()->getByName('My App');

// Create a new application
$newApp = $client->applications()->create([
    'name' => 'My Laravel App',
    'description' => 'A Laravel application'
]);

// Get OAuth2 configuration
$oauthConfig = $client->applications()->getOAuth2Config('app-id');

// Update OAuth2 configuration
$updatedConfig = $client->applications()->updateOAuth2Config('app-id', [
    'grantTypes' => ['authorization_code', 'refresh_token'],
    'callbackURLs' => ['https://myapp.com/auth/callback']
]);

// Regenerate client secret
$newSecret = $client->applications()->regenerateClientSecret('app-id');
```

### OAuth2 Authentication Flow

The package provides request classes to handle OAuth2 authentication flows. You'll need to set up your own routes and controllers:

```php
// In your controller
use Notrel\LaravelWso2is\Http\Requests\Wso2isLoginRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isAuthenticationRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isLogoutRequest;

// Initiate login
public function login()
{
    $loginRequest = new Wso2isLoginRequest();
    return redirect($loginRequest->getRedirectUrl());
}

// Handle callback
public function callback(Request $request)
{
    $authRequest = new Wso2isAuthenticationRequest($request);
    
    try {
        $user = $authRequest->getUser();
        // Handle successful authentication
        // Store user data, create session, etc.
        
        return redirect('/dashboard');
    } catch (\Exception $e) {
        // Handle authentication error
        return redirect('/login')->withErrors(['error' => 'Authentication failed']);
    }
}

// Handle logout
public function logout()
{
    $logoutRequest = new Wso2isLogoutRequest();
    
    // Clear local session
    auth()->logout();
    session()->flush();
    
    // Redirect to WSO2IS logout
    return redirect($logoutRequest->getRedirectUrl());
}
```

To initiate the OAuth2 flow, redirect users to your WSO2IS authorization endpoint:

```php
$authUrl = 'https://your-wso2is-instance.com/oauth2/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => config('services.wso2is.client_id'),
    'redirect_uri' => route('wso2is.callback'),
    'scope' => 'openid profile email',
    'state' => csrf_token()
]);

return redirect($authUrl);
```

## Testing

Run the test suite using Pest:

```bash
composer test
```

Or directly:

```bash
vendor/bin/pest
```

### Integration Testing with Real WSO2IS

To test against a real WSO2 Identity Server instance:

#### 1. Setup WSO2IS Application

1. Start WSO2 Identity Server
2. Access Management Console: `https://localhost:9443/carbon`
3. Login with admin credentials
4. Create a new OAuth application:
   - Go to **Main > Identity > Service Providers**
   - Click **Add** and create a new service provider
   - Configure **Inbound Authentication Configuration > OAuth/OpenID Connect**
   - Add required scopes: `internal_user_mgt_*`, `internal_group_mgt_*`, `internal_application_mgt_*`
   - Note down the **Client ID** and **Client Secret**

#### 2. Configure Test Environment

```bash
# Copy environment file
cp .env.integration.example .env.integration
```

Edit `.env.integration` with your WSO2IS details:

```env
WSO2IS_BASE_URL=https://localhost:9443
WSO2IS_CLIENT_ID=your-actual-client-id
WSO2IS_CLIENT_SECRET=your-actual-client-secret
WSO2IS_USERNAME=admin
WSO2IS_PASSWORD=admin
```

#### 3. Run Integration Tests

```bash
# Run all tests (unit + integration)
composer test:all

# Run only integration tests
composer test:integration

# Run specific integration test
vendor/bin/phpunit --configuration phpunit.integration.xml --filter UserManagementIntegrationTest
```

Integration tests cover:
- OAuth token acquisition and API access
- User CRUD operations via SCIM2 API
- Group management and membership
- Search/filter operations
- Error handling and edge cases

Tests automatically clean up created resources after each test.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email the repository maintainer instead of using the issue tracker.

## Credits

- [Don Mbelembe](https://github.com/donmbelembe)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
