# WSO2IS Laravel Package - Improvements & Middleware

## 🚀 New Features Added

### 1. **Session Middleware** (`ValidateSessionWithWso2is`)
- Automatically validates access tokens on protected routes
- Refreshes expired tokens using refresh tokens
- Logs out users when tokens are invalid
- Register in your routes: `Route::middleware('wso2is.session')`

### 2. **Request Classes** (Laravel WorkOS Style)
- `Wso2isLoginRequest` - Handle login redirects with OIDC parameters
- `Wso2isAuthenticationRequest` - Process OAuth2 callbacks
- `Wso2isLogoutRequest` - Handle logout with SSO support

### 3. **Enhanced User Model**
- Role checking: `$user->hasRole('admin')`, `$user->hasAnyRole(['admin', 'editor'])`
- Group checking: `$user->inGroup('developers')`, `$user->inAnyGroup(['qa', 'testers'])`
- Full name generation: `$user->getFullName()`
- Array conversion: `$user->toArray()`

### 4. **Main WSO2IS Class**
- Token validation and refresh logic
- User info retrieval from access tokens
- OIDC discovery document caching
- JWKs caching for performance

### 5. **Package Architecture** (Like Laravel WorkOS)
- **No predefined routes or controllers** - Developers create their own
- **Request classes** for clean authentication flows
- **Middleware** for session protection
- **Flexibility** to implement authentication however you want

## 📖 Quick Usage Examples

### Create Your Own Routes
```php
// In your routes/web.php
Route::get('/auth/login', function (Wso2isLoginRequest $request) {
    return $request->redirect(['prompt' => 'login']);
});

Route::get('/auth/callback', function (Wso2isAuthenticationRequest $request) {
    $user = $request->authenticate();
    return $request->redirect('/dashboard');
});
```

### Authentication Flow
```php
// Login
Route::get('/login', function (Wso2isLoginRequest $request) {
    return $request->redirect(['prompt' => 'login']);
});

// Callback
Route::get('/auth/callback', function (Wso2isAuthenticationRequest $request) {
    $user = $request->authenticate();
    return $request->redirect('/dashboard');
});

// Logout
Route::post('/logout', function (Wso2isLogoutRequest $request) {
    return $request->logout('/');
});
```

### Working with Users
```php
use Donmbelembe\LaravelWso2is\Wso2is;

$user = Wso2is::getUserFromToken($accessToken);

if ($user->hasRole('admin')) {
    // Admin functionality
}

if ($user->inAnyGroup(['qa', 'developers'])) {
    // Team-specific features
}
```

## 🧪 Testing
- 22 passing tests with Pest framework
- Unit tests for User model and HTTP client
- Feature tests for request classes and user management
- Integration tests for middleware (some skipped due to complexity)

## 🔄 Migration from Previous Version
1. Update namespaces from `Laravel\Wso2is` to `Donmbelembe\LaravelWso2is`
2. Add new middleware to routes that need session validation
3. Optionally replace manual auth flows with request classes
4. Update configuration to include `WSO2IS_REDIRECT_URI`

## 📋 What's Different from Laravel WorkOS?
- **More comprehensive**: Includes SCIM2 for user/group management
- **WSO2IS specific**: Tailored for WSO2 Identity Server features  
- **OIDC focus**: Full OpenID Connect implementation
- **Role/Group support**: Built-in role and group checking
- **Discovery support**: OIDC discovery document integration

Your Laravel WSO2IS package now provides the same developer experience as Laravel WorkOS but with more features specific to WSO2 Identity Server!
