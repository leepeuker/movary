# Getting started

After installing Movary, open its configured URL and complete the following steps.

## Create the initial administrator

When no users exist, Movary opens the initial setup page automatically. Enter an email address, username, and password to create the first administrator.

The account can alternatively be created from the server command line:

```bash
php bin/console.php user:create admin@example.com 'replace-with-secure-password' admin 1
```

The final `1` grants the Admin role. Passwords must contain at least 15 characters. CLI-created users are enabled and email-verified by default; use `--isEnabled=0` or `--emailVerified=0` to override either state.

Run `php bin/console.php` without a command to list all available CLI commands.

## Review the settings

Open **Settings** after signing in:

- **Account** contains personal preferences, security, integrations, and data-management options.
- **Server** contains installation-wide settings, user management, email configuration, and background jobs. These pages require the Admin role.

See the [User guide](user-guide/index.md) for an overview of the account settings available to every user.

See [Administration](administration.md) for an overview of installation-wide configuration and maintenance tasks.

Environment variables override values stored through the web UI. See [Configuration](configuration.md) for the complete reference.

## Configure email (optional)

Outgoing email enables password resets and optional email verification. Follow [Email operations](email-operations.md) to configure and test SMTP before enabling verification.

## Add users

Administrators can create accounts under **Settings → Server → Users** or through the CLI. An installation can also allow users to create their own accounts through public registration.

See [User management](user-management.md) for roles, account states, registration approval, and email-verification behavior.

## Verify background processing

Movary uses the `jobs:process` worker for background work, including verification and password reset emails. The standard Docker image runs the worker by default. Manual installations must configure Supervisor or cron as described in [Manual installation](install/manual.md#job-processing). See [Job processing](job-processing.md) for queue monitoring, recurring schedules, and troubleshooting.

## Maintain metadata

Administrators can schedule recurring TMDB metadata and IMDb rating updates under **Settings → Server → Job Schedules**. See [TMDB data](features/tmdb-data.md) and [IMDb ratings](features/imdb-rating.md) for the available jobs and filters.

Set `TMDB_ENABLE_IMAGE_CACHING=1` to store TMDB images locally. See [TMDB data](features/tmdb-data.md#image-cache) for configuration and synchronization details.
