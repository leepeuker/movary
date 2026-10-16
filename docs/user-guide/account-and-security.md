# Account and security

Movary separates general account preferences from sign-in and security settings. Open **Settings → Account** to manage both.

## Change your username

Open **Settings → Account → General**, edit **Username**, and select **Submit**. Usernames must contain only letters and numbers and must be unique within the Movary installation.

Your username is part of the URL for your dashboard, history, watchlist, and other user pages. Changing it also changes those URLs. Review [Privacy and sharing](privacy-and-sharing.md) before sharing a user-page link.

## Change your email address

Open **Settings → Account → Security**, enter the new email address and your current password, and select **Submit**.

What happens next depends on the server's email-verification policy:

- Without required verification, the new address becomes active immediately.
- With required verification, the current address remains active for sign-in while the new address is marked as pending. Use the link sent to the new address to complete the change.

While a change is pending, use **Resend** to request another verification email or **Cancel** to keep the current address. Contact an administrator if verification messages do not arrive; the administrator can follow [Email operations](../email-operations.md#troubleshooting).

## Change your password

Open **Settings → Account → Security** and enter:

1. Your current password.
2. A new password containing at least 15 characters.
3. The same new password again.

Select **Submit**. A successful password change ends all of your web sessions, including the current one, so you must sign in again. Personal API tokens remain active and can be managed separately under [API authentication](api-authentication.md).

## Two-factor authentication

Movary supports time-based one-time passwords (TOTP) from authenticator applications.

To enable two-factor authentication:

1. Open **Settings → Account → Security**.
2. Select **Enable Two-Factor Authentication**.
3. Scan the QR code with an authenticator application, or enter the displayed secret manually.
4. Enter the six-digit authentication code to confirm the setup.

Future sign-ins require a current code from the authenticator application in addition to the email address and password.

To remove this requirement, return to the same page and select **Disable Two-Factor Authentication**.

## Reset a forgotten password

The login page shows **Forgot password?** when the administrator has configured outgoing email and the public application URL. Enter your account email to request a reset link. Movary shows the same confirmation whether or not an account exists for the address.

Reset links expire after 15 minutes. Completing a reset ends all web sessions and revokes every personal API token for the account. Create new tokens afterward for any clients or automation that still need API access.

If **Forgot password?** is unavailable or the message does not arrive, contact an administrator. Administrators can send a reset message or set a new password through server user management.
