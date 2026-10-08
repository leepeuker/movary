## Introduction

There are two main ways how to set your server configuration:

- Environment variables
- Web UI (Settings -> Server), this config is stored in the database

!!! Info

    Environment variables have the highest priority and overwrite everything else as long as they are set.

## Environment variables

The `Web UI` column is set to yes if an environment variable can alternatively be set via the web UI.

### General

| NAME                                        | DEFAULT VALUE | INFO                                                                           | Web UI |
|:--------------------------------------------|:-------------:|:-------------------------------------------------------------------------------|:------:|
| `TMDB_API_KEY`                              |       -       | **Required** (get key [here](https://www.themoviedb.org/settings/api))         |  yes   |
| `APPLICATION_SECRET`                        |       -       | **Required**, stable application secret; 64 hexadecimal characters              |        |
| `APPLICATION_URL`                           |       -       | Public base url of the application (e.g. `htttp://localhost`)                  |  yes   |
| `APPLICATION_NAME`                          |   `Movary`    | Application name, displayed e.g. as brand name in the navbar                   |  yes   |
| `TMDB_ENABLE_IMAGE_CACHING`                 |      `0`      | More info [here](features/tmdb-data.md#image-cache)                            |        |
| `ENABLE_REGISTRATION`                       |      `0`      | Enables public user registration                                               |        |
| `MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING` |     `15`      | Minimum time between background jobs processing                                |        |
| `TIMEZONE`                                  |     `UTC`     | Supported timezones [here](https://www.php.net/manual/en/timezones.php)        |  yes   |
| `LOGIN_ATTEMPT_LIMIT`                       |      `5`      | Positive number of failed attempts allowed before login is temporarily blocked |        |
| `LOGIN_ATTEMPT_WINDOW_IN_SECONDS`           |     `900`     | Positive period during which failed login attempts are counted (seconds)       |        |
| `DEFAULT_LOGIN_EMAIL`                       |       -       | Email address to always autofill on login page                                 |        |
| `DEFAULT_LOGIN_PASSWORD`                    |       -       | Password to always autofill on login page                                      |        |
| `TOTP_ISSUER`                               |   `Movary`    | The issuer used when setting up two factor authentication                      |        |

Generate `APPLICATION_SECRET` with `openssl rand -hex 32`. Movary uses it as the root secret for purpose-specific cryptographic keys. Keep the value stable across restarts and identical across all Movary instances. It can also be supplied through `APPLICATION_SECRET_FILE`, using the environment variable file convention described in the Docker installation documentation.

When TLS terminates at a reverse proxy, set `APPLICATION_URL` to the public `https://` URL so Movary marks authentication cookies as secure.

### Database

Required to run the application

| NAME                              |      DEFAULT VALUE      | INFO                                                   |
|:----------------------------------|:-----------------------:|:-------------------------------------------------------|
| `DATABASE_MODE`                   |        `sqlite`         | `sqlite` or `mysql`                                    |
| `DATABASE_SQLITE`                 | `storage/movary.sqlite` |                                                        |
| `DATABASE_MYSQL_HOST`             |            -            | Required when using MySQL without a Unix socket        |
| `DATABASE_MYSQL_PORT`             |         `3306`          | Used when connecting to MySQL via TCP                  |
| `DATABASE_MYSQL_SOCKET`           |            -            | Unix socket path; when set, host and port are ignored  |
| `DATABASE_MYSQL_NAME`             |            -            | Required when mode is `mysql`                          |
| `DATABASE_MYSQL_USER`             |            -            | Required when mode is `mysql`                          |
| `DATABASE_MYSQL_PASSWORD`         |            -            | Required when mode is `mysql`                          |
| `DATABASE_MYSQL_CHARSET`          |        `utf8mb4`        |                                                        |
| `DATABASE_MYSQL_COLLATION`        |  `utf8mb4_unicode_ci`   |                                                        |
| `DATABASE_DISABLE_AUTO_MIGRATION` |           `0`           | On default docker runs migrations on container startup |

`DATABASE_MYSQL_SOCKET` requires the socket file to be available to the Movary process. For containerized installations, the socket directory must be mounted into the application container; the default Docker Compose setup uses TCP.

### Third party integrations

Required for some third party integrations. Only necessary if the relevant third party integrations should be enabled.

| NAME                         | DEFAULT VALUE | INFO                                                                                               | Web UI |
|:-----------------------------|:-------------:|:---------------------------------------------------------------------------------------------------|:------:|
| `PLEX_IDENTIFIER`            |       -       | Required for Plex Authentication. Generate with e.g. `openssl rand -base64 32`                     |        |
| `PLEX_APP_NAME`              |   `Movary`    | Used for Plex Authentication                                                                       |        |
| `PLEX_VALIDATE_URL_SAFE`     |      `0`      | Enable SSRF protection for Plex server URLs (blocks localhost, private IPs, internal DNS)          |        |
| `JELLYFIN_DEVICE_ID`         |       -       | Required for Jellyfin Authentication. Generate with e.g. `openssl rand -base64 32`                 |        |
| `JELLYFIN_APP_NAME`          |   `Movary`    | Used for Jellyfin Authentication                                                                   |        |
| `JELLYFIN_VALIDATE_URL_SAFE` |      `0`      | Enable SSRF protection for Jellyfin server URLs (blocks localhost, private IPs, internal DNS)      |        |

### Email

Outgoing email features are disabled by default. Set `EMAIL_ENABLED=1` or enable them in the Web UI after configuring SMTP.

| NAME                | DEFAULT VALUE | INFO                                                   | Web UI |
|:--------------------|:-------------:|:-------------------------------------------------------|:------:|
| `EMAIL_ENABLED`     |      `0`      | `1` enables outgoing email features                     |  yes   |
| `SMTP_HOST`         |       -       | SMTP server hostname                                   |  yes   |
| `SMTP_PORT`         |       -       | SMTP server port, from `1` through `65535`             |  yes   |
| `SMTP_FROM_ADDRESS` |       -       | Valid email address used as the sender                 |  yes   |
| `SMTP_ENCRYPTION`   |       -       | Empty for none; otherwise `ssl` or `tls`               |  yes   |
| `SMTP_WITH_AUTH`    |      `0`      | `1` enables username/password authentication           |  yes   |
| `SMTP_USER`         |       -       | Required when authentication is enabled                |  yes   |
| `SMTP_PASSWORD`     |       -       | Required when authentication is enabled                |  yes   |

### Logging

| NAME                      | DEFAULT VALUE | INFO                                                                           |
|:--------------------------|:-------------:|:-------------------------------------------------------------------------------|
| `LOG_LEVEL`               |   `warning`   | Uses [RFC 5424](https://datatracker.ietf.org/doc/html/rfc5424) severity levels |
| `LOG_ENABLE_STACKTRACE`   |      `0`      | Only needed for debugging                                                      |
| `LOG_ENABLE_FILE_LOGGING` |      `1`      | Persist logs to a file in directory `storage/logs`                             |
