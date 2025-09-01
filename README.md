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
```

#### 2. Or Create a Controller

```php
// app/Http/Controllers/AuthController.php
<?php

namespace App\Http\Controllers;

use Notrel\LaravelWso2is\Http\Requests\Wso2isLoginRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isAuthenticationRequest;
use Notrel\LaravelWso2is\Http\Requests\Wso2isLogoutRequest;

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

### Using the Facade (Advanced)

```php
use Notrel\LaravelWso2is\Facades\Wso2is;

// Get access token
$token = Wso2is::getAccessToken();

// Make raw API requests
$users = Wso2is::get('/scim2/Users');
```

### User Management

```php
use Notrel\LaravelWso2is\Facades\Wso2is;

// List all users
$users = Wso2is::users()->list();

// Get a specific user
$user = Wso2is::users()->get('user-id');

// Get user by username
$user = Wso2is::users()->getByUsername('john.doe');

// Get user by email
$user = Wso2is::users()->getByEmail('john@example.com');

// Create a new user
$newUser = Wso2is::users()->create([
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
$updatedUser = Wso2is::users()->update('user-id', [
    'name' => [
        'givenName' => 'John',
        'familyName' => 'Smith'
    ]
]);

// Delete a user
Wso2is::users()->delete('user-id');
```

### Group Management

```php
use Notrel\LaravelWso2is\Facades\Wso2is;

// List all groups
$groups = Wso2is::groups()->list();

// Get a specific group
$group = Wso2is::groups()->get('group-id');

// Get group by name
$group = Wso2is::groups()->getByName('Administrators');

// Create a new group
$newGroup = Wso2is::groups()->create([
    'displayName' => 'New Group',
    'members' => [
        [
            'value' => 'user-id-1',
            'display' => 'user-id-1'
        ]
    ]
]);

// Add user to group
Wso2is::groups()->addUser('group-id', 'user-id');

// Remove user from group
Wso2is::groups()->removeUser('group-id', 'user-id');

// Delete a group
Wso2is::groups()->delete('group-id');
```

### Application Management

```php
use Notrel\LaravelWso2is\Facades\Wso2is;

// List all applications
$applications = Wso2is::applications()->list();

// Get a specific application
$app = Wso2is::applications()->get('app-id');

// Get application by name
$app = Wso2is::applications()->getByName('My App');

// Create a new application
$newApp = Wso2is::applications()->create([
    'name' => 'My Laravel App',
    'description' => 'A Laravel application'
]);

// Get OAuth2 configuration
$oauthConfig = Wso2is::applications()->getOAuth2Config('app-id');

// Update OAuth2 configuration
$updatedConfig = Wso2is::applications()->updateOAuth2Config('app-id', [
    'grantTypes' => ['authorization_code', 'refresh_token'],
    'callbackURLs' => ['https://myapp.com/auth/callback']
]);

// Regenerate client secret
$newSecret = Wso2is::applications()->regenerateClientSecret('app-id');
```

### OAuth2 Authentication Flow

The package provides a callback route for handling OAuth2 authentication:

```php
// The callback route is automatically registered at: /wso2is/callback
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

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email the repository maintainer instead of using the issue tracker.

## Credits

- [Don Mbelembe](https://github.com/donmbelembe)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
