# Administration

The administration guides explain how to configure and operate a Movary installation. These tasks require the Admin role or, where noted, command-line access to the server.

Open **Settings → Server** to manage installation-wide settings. Environment variables take priority over values saved through the web interface.

## Set up the installation

- [Getting started](getting-started.md) covers the initial administrator, account setup, email, background processing, and metadata maintenance.
- [Configuration](configuration.md) lists the available environment variables and indicates which settings can also be changed through the web interface.
- [User management](user-management.md) explains roles, account states, public registration, approval, and email verification.

## Operate the installation

- [Email operations](email-operations.md) covers SMTP configuration, verification messages, password resets, and delivery troubleshooting.
- [TMDB data](features/tmdb-data.md) explains metadata synchronization and the optional local image cache.
- [IMDb ratings](features/imdb-rating.md) explains how to update public IMDb ratings for local movies.
- [Job processing](development/job-processing.md) explains the background worker, recurring schedules, queue statuses, and failed-job troubleshooting.
- [Backup and restore](backup-and-restore.md) describes what a complete backup contains and how to verify a restored installation.

## Installation methods

The [Docker installation](install/docker.md) is recommended and includes the background worker and automatic database migrations. The [manual installation](install/manual.md) documents the additional web server, scheduler, and worker configuration required when running Movary without the provided container.

Users do not need the Admin role to manage their own preferences, security, integrations, or personal data. Those tasks are covered in the [User guide](user-guide/index.md).
