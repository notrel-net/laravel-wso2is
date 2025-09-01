# WSO2IS OAuth Application Scopes for Integration Tests (WSO2IS 7.1)

## WSO2IS 7.1 Specific Notes

WSO2IS 7.1 has different scope names and authentication methods compared to newer versions:

### Authentication Method
- Uses **OAuth2 Client Credentials Grant** for machine-to-machine authentication (OIDC Compliant)
- Client credentials should be sent in Authorization header as Bearer token
- May require tenant domain in URLs: `/t/carbon.super/`

### Required Scopes for WSO2IS 7.1

## User Management (SCIM2)
- `internal_user_mgt_create`
- `internal_user_mgt_list` 
- `internal_user_mgt_view`
- `internal_user_mgt_update`
- `internal_user_mgt_delete`

## Group Management (SCIM2)
- `internal_group_mgt_create`
- `internal_group_mgt_list`
- `internal_group_mgt_view` 
- `internal_group_mgt_update`
- `internal_group_mgt_delete`

## Application Management
- `internal_application_mgt_create`
- `internal_application_mgt_view`
- `internal_application_mgt_update`
- `internal_application_mgt_delete`

## How to Configure OAuth Application in WSO2IS 7.1

1. Login to WSO2IS Management Console: `https://sso.notrel.net/carbon`
2. Go to **Main > Identity > Service Providers**
3. Find your application (Client ID: `U15P5Cb0T3tCpacM1advAcSQdTAa`)
4. Click **Edit**
5. Expand **"Inbound Authentication Configuration"**
6. Click **"OAuth/OpenID Connect Configuration"**
7. Click **"Edit"**
8. Configure the following:
   - **Allowed Grant Types**: Select `Client Credentials`
   - **Scope Validators**: Add `Role based scope validator`
   - **Allowed Scopes**: Add all scopes listed above (one per line)
9. Click **"Update"**

## Version Detection

The package automatically detects WSO2IS version and tries multiple endpoint formats:

- `/api/server/v1/` (newer versions)
- `/identity/application-mgt/v1.0/` (WSO2IS 7.1)
- `/t/carbon.super/api/server/v1/` (tenant-specific)
