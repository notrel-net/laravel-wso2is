# WSO2 Identity Server 7.1 Configuration Guide

## Overview
WSO2IS 7.1 is the latest version and has specific requirements for OAuth2 and SCIM2 API access.

## Required OAuth2 Application Setup

### 1. Create Service Provider
1. Login to WSO2IS Management Console: `https://sso.notrel.net/carbon`
2. Navigate to **Main > Identity > Service Providers**
3. Click **Add** to create a new Service Provider
4. Enter a name (e.g., "Laravel Integration")

### 2. Configure OAuth2/OpenID Connect
1. Expand **Inbound Authentication Configuration**
2. Click **OAuth/OpenID Connect Configuration > Configure**
3. Set the following:

#### Grant Types (Required for API Access)
- ✅ **Client Credentials** (Essential for machine-to-machine)
- ✅ **Authorization Code** (For user authentication)
- ✅ **Refresh Token** (For token refresh)

#### Callback URLs
```
http://localhost:8000/auth/wso2is/callback
https://your-app.com/auth/wso2is/callback
```

#### Scopes (WSO2IS 7.1 Required Scopes)
```
openid
profile
email
internal_user_mgt_create
internal_user_mgt_list
internal_user_mgt_view
internal_user_mgt_update
internal_user_mgt_delete
internal_group_mgt_create
internal_group_mgt_list
internal_group_mgt_view
internal_group_mgt_update
internal_group_mgt_delete
internal_application_mgt_view
internal_application_mgt_create
internal_application_mgt_update
internal_application_mgt_delete
```

### 3. Advanced Settings for WSO2IS 7.1
- **Token Endpoint Auth Method**: `client_secret_basic`
- **Enable PKCE**: `Optional` or `Disabled` for server-side apps
- **Enable Audience Restriction**: `No`
- **Enable Request Object Signature Validation**: `No`

## Environment Configuration

```env
# OAuth2 Bearer Tokens (OIDC Compliant)
WSO2IS_BASE_URL=https://sso.notrel.net
WSO2IS_CLIENT_ID=your-client-id
WSO2IS_CLIENT_SECRET=your-client-secret
```

## OIDC Compliance Features

### OAuth2 Client Credentials Grant
- ✅ **RFC 6749 Compliant** - Standard OAuth2 flow
- ✅ **Scope-based Authorization** - Granular permissions
- ✅ **Token-based Authentication** - Secure and auditable
- ✅ **Enterprise Ready** - Suitable for production

### Security Benefits
- **Audit Trails**: All API calls are logged with OAuth2 context
- **Token Expiration**: Automatic security through token lifecycle
- **Scope Limitation**: Access restricted to granted scopes only
- **No Credential Sharing**: Client credentials stay secure

## WSO2IS 7.1 Specific Features

### API Endpoints (Confirmed for 7.1)
- **SCIM2 Users**: `/scim2/Users`
- **SCIM2 Groups**: `/scim2/Groups`
- **OAuth2 Token**: `/oauth2/token`
- **OAuth2 Authorize**: `/oauth2/authorize`
- **Applications**: `/api/server/v1/applications`

### Headers Required
```
Authorization: Bearer {access_token}
Accept: application/scim+json
Content-Type: application/scim+json
```

### Common Issues & Solutions

#### 403 Forbidden Errors
1. **Missing Scopes**: Ensure all required scopes are added to OAuth application
2. **Role Assignment**: Admin user must have required roles
3. **Tenant Context**: Ensure you're in the correct tenant (usually `carbon.super`)

#### SSL/TLS Issues
For development with self-signed certificates:
```env
WSO2IS_SKIP_SSL_VERIFY=true
```

#### Rate Limiting
WSO2IS 7.1 has rate limiting. For testing:
- Add delays between API calls
- Use connection pooling
- Implement exponential backoff

## Testing the Configuration

### 1. Test OAuth Token
```bash
curl -X POST https://sso.notrel.net/oauth2/token \
  -H "Authorization: Basic $(echo -n 'client_id:client_secret' | base64)" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials&scope=internal_user_mgt_list"
```

### 2. Test SCIM2 API
```bash
curl -X GET https://sso.notrel.net/scim2/Users \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/scim+json"
```

## Troubleshooting Commands

```bash
# Check WSO2IS version
curl -k https://sso.notrel.net/carbon/admin/layout/menu_server_url.jsp

# Check available scopes
curl -X GET https://sso.notrel.net/.well-known/openid_configuration

# Test connectivity
curl -k -I https://sso.notrel.net/carbon/
```
