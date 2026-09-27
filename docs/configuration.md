## Introduction

There are two main ways how to set your server configuration:

- Environment variables
- Web UI (Settings -> Server), this config is stored in the database

!!! Info

    Environment variables have the highest priority and overwrite everything else as long as they are set.

## Environment variables

The `Web UI` column is set to yes if an environment variable can alternatively be set via the web UI.

### General

| NAME                                        | DEFAULT VALUE | INFO                                                                    | Web UI |
|:--------------------------------------------|:-------------:|:------------------------------------------------------------------------|:------:|
| `TMDB_API_KEY`                              |       -       | **Required** (get key [here](https://www.themoviedb.org/settings/api))  |  yes   |
| `APPLICATION_URL`                           |       -       | Public base url of the application (e.g. `htttp://localhost`)           |  yes   |
| `APPLICATION_NAME`                          |   `Movary`    | Application name, displayed e.g. as brand name in the navbar            |  yes   |
| `TMDB_ENABLE_IMAGE_CACHING`                 |      `0`      | More info [here](features/tmdb-data.md#image-cache)                     |        |
| `ENABLE_REGISTRATION`                       |      `0`      | Enables public user registration                                        |        |
| `MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING` |     `15`      | Minimum time between background jobs processing                         |        |
| `TIMEZONE`                                  |     `UTC`     | Supported timezones [here](https://www.php.net/manual/en/timezones.php) |  yes   |
| `DEFAULT_LOGIN_EMAIL`                       |       -       | Email address to always autofill on login page                          |        |
| `DEFAULT_LOGIN_PASSWORD`                    |       -       | Password to always autofill on login page                               |        |
| `TOTP_ISSUER`                               |   `Movary`    | The issuer used when setting up two factor authentication               |        |

### Database

Required to run the application

| NAME                              |      DEFAULT VALUE      | INFO                                                   |
|:----------------------------------|:-----------------------:|:-------------------------------------------------------|
| `DATABASE_MODE`                   |        `sqlite`         | `sqlite` or `mysql`                                    |
| `DATABASE_SQLITE`                 | `storage/movary.sqlite` |                                                        |
| `DATABASE_MYSQL_HOST`             |            -            | Required when mode is `mysql`                          |
| `DATABASE_MYSQL_PORT`             |         `3306`          |                                                        |
| `DATABASE_MYSQL_NAME`             |            -            | Required when mode is `mysql`                          |
| `DATABASE_MYSQL_USER`             |            -            | Required when mode is `mysql`                          |
| `DATABASE_MYSQL_PASSWORD`         |            -            | Required when mode is `mysql`                          |
| `DATABASE_MYSQL_CHARSET`          |        `utf8mb4`        |                                                        |
| `DATABASE_DISABLE_AUTO_MIGRATION` |           `0`           | On default docker runs migrations on container startup |

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

Configure outgoing email either in `Settings -> Server -> Email` or with the environment variables below.

Environment variables take precedence over database values. Settings provided through the environment are shown as disabled in the web UI and cannot be overwritten there. SMTP passwords are never displayed; the UI only shows `***` when a password is configured.

| NAME                | DEFAULT VALUE | INFO                                                   | Web UI |
|:--------------------|:-------------:|:-------------------------------------------------------|:------:|
| `SMTP_HOST`         |       -       | SMTP server hostname                                   |  yes   |
| `SMTP_PORT`         |       -       | SMTP server port, from `1` through `65535`             |  yes   |
| `SMTP_FROM_ADDRESS` |       -       | Valid email address used as the sender                 |  yes   |
| `SMTP_ENCRYPTION`   |       -       | Empty for none; otherwise `ssl` or `tls`               |  yes   |
| `SMTP_WITH_AUTH`    |      `0`      | `1` enables username/password authentication           |  yes   |
| `SMTP_USER`         |       -       | Required when authentication is enabled                |  yes   |
| `SMTP_PASSWORD`     |       -       | Required when authentication is enabled                |  yes   |

A typical authenticated SMTP configuration using explicit TLS is:

```dotenv
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_FROM_ADDRESS=movary@example.com
SMTP_ENCRYPTION=tls
SMTP_WITH_AUTH=1
SMTP_USER=movary@example.com
SMTP_PASSWORD=replace-with-a-secret
```

For an SMTP relay that does not require authentication:

```dotenv
SMTP_HOST=smtp.example.com
SMTP_PORT=25
SMTP_FROM_ADDRESS=movary@example.com
SMTP_ENCRYPTION=
SMTP_WITH_AUTH=0
```

The **Test** action uses unsaved values currently entered in editable fields. Omitted values, including an unchanged password, are resolved from the stored server configuration. Environment-managed values always take precedence and are resolved on the server.

Disabling authentication through the web UI removes database-managed SMTP username and password values. Environment-managed credentials are not changed.

!!! Warning

    Older Movary versions used the misspelled encryption value `tsl`. Database values are migrated automatically, but environment configurations cannot be migrated. Change `SMTP_ENCRYPTION=tsl` to `SMTP_ENCRYPTION=tls` manually.

### Logging

| NAME                      | DEFAULT VALUE | INFO                                                                           |
|:--------------------------|:-------------:|:-------------------------------------------------------------------------------|
| `LOG_LEVEL`               |   `warning`   | Uses [RFC 5424](https://datatracker.ietf.org/doc/html/rfc5424) severity levels |
| `LOG_ENABLE_STACKTRACE`   |      `0`      | Only needed for debugging                                                      |
| `LOG_ENABLE_FILE_LOGGING` |      `1`      | Persist logs to a file in directory `storage/logs`                             |
