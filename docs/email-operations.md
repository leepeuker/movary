# Email operations

Movary uses outgoing email for email verification and password resets. Administrators configure it under **Settings → Server → Email** or with environment variables.

## Requirements

Email verification and password resets require all of the following:

- Email support enabled with `EMAIL_ENABLED=1` or through the server email settings.
- A valid SMTP host, port, sender address, encryption mode, and optional authentication credentials.
- An `APPLICATION_URL` that recipients can open so links in messages point back to Movary.
- A recurring `jobs:process` worker to process queued verification and password reset emails. The standard Docker image runs this worker by default.

Environment variables take priority over values stored through the web UI. Fields configured through the environment are read-only in the UI. See [Configuration](configuration.md#email) for every email setting.

## Configure and test SMTP

Open **Settings → Server → Email**, enable email support, and enter the SMTP settings supplied by the email provider. Supported encryption values are none, `ssl`, and `tls`; SMTP authentication can be enabled separately.

Use **Test** to send a test message to a chosen recipient. The test is sent immediately and validates the supplied SMTP settings without waiting for the job worker.

After saving the configuration, make sure `jobs:process` runs regularly. Docker installations include job processing in the standard setup. Manual installations must configure Supervisor or cron as described in [Manual installation](install/manual.md#job-processing).

## Email verification

Set `EMAIL_VERIFICATION_REQUIRED=1` to require verified email addresses. This setting is valid only when email support, SMTP, and `APPLICATION_URL` are configured.

Publicly registered users receive a verification email and cannot sign in until verification is complete. Verification is independent of administrator approval: an account may need one or both before it is available. See the [public registration matrix](user-management.md#public-registration).

Verification links expire after 24 hours. Users can request another message from the login page. Administrators can resend messages for pending email changes from **Settings → Server → Users**. For any unverified account, administrators can run:

```bash
php bin/console.php user:email:request-verification USER_ID
```

When verification is required and a user changes their email address, Movary keeps the current address active and stores the new address as pending. The new address becomes active after verification. An administrator may instead approve or cancel the pending change from user management.

## Password resets

When email support is available, the login page shows **Forgot password?**. Password reset links expire after 15 minutes.

Administrators can send a reset email from **Settings → Server → Users**. Pending resets can be reviewed and revoked under **Settings → Server → Password Resets**.

The equivalent CLI commands are:

```bash
php bin/console.php user:password:request-reset USER_ID
php bin/console.php user:password:list-resets
php bin/console.php user:password:revoke-reset USER_ID
php bin/console.php user:password:revoke-all-resets
```

If email delivery is unavailable, an administrator can set a password directly from an interactive terminal:

```bash
php bin/console.php user:password:set USER_ID
```

## Troubleshooting

If test messages work but verification or reset messages do not arrive:

1. Confirm that the `jobs:process` worker is running.
2. Review **Settings → Server → Job Queue** for failed email jobs.
3. Confirm that `APPLICATION_URL` is the public URL users can open.
4. Check the application logs for SMTP or job-processing errors.

If the **Test** action fails, verify the SMTP host, port, encryption mode, sender address, and authentication credentials. Configuration supplied through environment variables must be changed in the deployment environment and requires the application to be restarted.
