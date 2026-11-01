# User management

Administrators manage user accounts under **Settings → Server → Users**. Administrators with server command-line access can also create accounts through the CLI. Public registration can optionally allow users to create their own accounts.

Administrators can also manage accounts with a personal API token under `/api/v1/admin/users`. See the [OpenAPI specification](openapi.json) for request and response formats.

Users can update their own email, password, username, and two-factor authentication under **Settings → Account**. See [Account and security](user-guide/account-and-security.md) for these self-service actions.

## Roles and account states

Role, account status, and email verification are independent:

| Dimension | Values | Effect |
|:----------|:-------|:-------|
| Role | User, Admin | Admins can manage users and server settings. |
| Account status | Enabled, Disabled | Disabled accounts cannot sign in. Disabling an account ends its active web sessions. |
| Email status | Verified, Unverified | An unverified account cannot sign in when `EMAIL_VERIFICATION_REQUIRED=1`. |
| Email change | None, Pending | The current email remains active until the pending address is verified or approved by an administrator. |

An account may therefore be enabled but unable to sign in because its email is unverified. Likewise, verifying an email does not enable a disabled account.

## Initial administrator

When no users exist, visiting Movary opens the initial setup page even if public registration is disabled. The account created there is an enabled administrator with a verified email address.

To create the initial administrator from the CLI, explicitly set the final `isAdmin` argument:

```bash
php bin/console.php user:create admin@example.com 'replace-with-secure-password' admin 1
```

The password must contain at least 15 characters. CLI-created accounts are enabled and email-verified by default.

For unattended setup, set `INITIAL_ADMIN_EMAIL`, `INITIAL_ADMIN_USERNAME`, and `INITIAL_ADMIN_PASSWORD` before starting the Docker container. You can use `INITIAL_ADMIN_PASSWORD_FILE` instead of the direct password setting. After database migrations, startup creates the enabled, email-verified administrator only when no users exist. Existing users are never modified on restart. If the settings are incomplete or invalid on an empty database, startup fails with an error; if none are set, the web setup page remains available. Remove the bootstrap credentials from the environment after setup when practical.

## Creating users as an administrator

Open **Settings → Server → Users** and select **Create user**. Administrators choose whether the new account has the Admin role. Accounts created through this form are enabled and email-verified immediately; the public registration defaults do not apply.

The equivalent CLI command is:

```bash
php bin/console.php user:create user@example.com 'replace-with-secure-password' username
```

The optional flags `--isEnabled=0` and `--emailVerified=0` create an account that is disabled or unverified. Add the positional `isAdmin` value `1` to create an administrator.

## Public registration

Set `ENABLE_REGISTRATION=1` to show **Create account** on the login page and allow public registration. When registration is disabled and users already exist, the registration page redirects to the login page.

The initial state of a publicly registered account depends on these settings:

| `REGISTRATION_DEFAULT_USER_ENABLED` | `EMAIL_VERIFICATION_REQUIRED` | Account availability |
|:-----------------------------------:|:-----------------------------:|:---------------------|
| `0` | `0` | Waits for administrator approval |
| `0` | `1` | Waits for email verification and administrator approval |
| `1` | `0` | Available immediately |
| `1` | `1` | Waits for email verification |

Approval means enabling the account under **Settings → Server → Users**. Email verification requires working outgoing email; see [Email operations](email-operations.md#email-verification).

## Managing accounts

Select a user under **Settings → Server → Users** to:

- Change the username, email, or password.
- Grant or remove the Admin role.
- Enable or disable the account.
- Send a password reset email when email support is available.
- Delete the account.

An administrator cannot disable their own account. Disabling another account revokes its authentication tokens and ends active web sessions.

When email verification is required, changing an email creates a pending change instead of immediately replacing the current address. Administrators can resend its verification email, approve the pending address manually, or cancel the change.

## CLI commands

Run `php bin/console.php <command> --help` for complete arguments and options.

| Command | Purpose |
|:--------|:--------|
| `user:list` | List user IDs and email addresses. |
| `user:create` | Create a user and choose its role and initial states. |
| `user:bootstrap-admin` | Create the first administrator from `INITIAL_ADMIN_*` settings if no users exist. |
| `user:update` | Update user account data or role. |
| `user:delete` | Delete a user. |
| `user:email:request-verification` | Queue another verification email. |
| `user:password:set` | Set a password through hidden interactive input. |
| `user:password:request-reset` | Queue a password reset email. |
| `user:password:list-resets` | List pending password reset requests. |
| `user:password:revoke-reset` | Revoke one user's pending password reset. |
| `user:password:revoke-all-resets` | Revoke every pending password reset. |
