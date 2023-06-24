## Introduction

There are two main ways how to set your server configuration:

- Environment variables
- Web UI (Settings -> Server), this config is stored in the database

!!! Info

    Environment variables have the highest priority and overwrite everything else as long as they are set.

## Environment variables

The `Web UI` column is set to yes if an environment variable can alternatively be set via the web UI.

### General

| NAME                                        |   DEFAULT VALUE   | INFO                                                                    | Web UI |
|:--------------------------------------------|:-----------------:|:------------------------------------------------------------------------|:------:|
| `TMDB_API_KEY`                              |         -         | **Required** (get key [here](https://www.themoviedb.org/settings/api))  |  yes   |
| `APPLICATION_URL`                           |         -         | Public base url of the application (e.g. `htttp://localhost`)           |  yes   |
| `TMDB_ENABLE_IMAGE_CACHING`                 |        `0`        | More info [here](features/tmdb-data.md#image-cache)                     |        |
| `ENABLE_REGISTRATION`                       |        `0`        | Enables public user registration                                        |        |
| `MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING` |       `15`        | Minimum time between background jobs processing                         |        |
| `TIMEZONE`                                  | `"Europe/Berlin"` | Supported timezones [here](https://www.php.net/manual/en/timezones.php) |        |
| `DEFAULT_LOGIN_EMAIL`                       |         -         | Email address to always autofill on login page                          |        |
| `DEFAULT_LOGIN_PASSWORD`                    |         -         | Password to always autofill on login page                               |        |

### Database

Required to run the application

| NAME                      |      DEFAULT VALUE      | INFO                             |
|:--------------------------|:-----------------------:|:---------------------------------|
| `DATABASE_MODE`           |            -            | **Required** `sqlite` or `mysql` |
| `DATABASE_SQLITE`         | `storage/movary.sqlite` |                                  |
| `DATABASE_MYSQL_HOST`     |            -            | Required when mode is `mysql`    |
| `DATABASE_MYSQL_PORT`     |         `3306`          |                                  |
| `DATABASE_MYSQL_NAME`     |            -            | Required when mode is `mysql`    |
| `DATABASE_MYSQL_USER`     |            -            | Required when mode is `mysql`    |
| `DATABASE_MYSQL_PASSWORD` |            -            | Required when mode is `mysql`    |
| `DATABASE_MYSQL_CHARSET`  |        `utf8mb4`        |                                  |

### Email

Required when email support is wanted

| NAME                | DEFAULT VALUE | INFO                                 | Web UI |
|:--------------------|:-------------:|:-------------------------------------|:------:|
| `SMTP_HOST`         |       -       |                                      |  yes   |
| `SMTP_PORT`         |       -       |                                      |  yes   |
| `SMTP_FROM_ADDRESS` |       -       | Email address used as sender address |  yes   |
| `SMTP_ENCRYPTION`   |       -       | `SSL` and `TSL` supported            |  yes   |
| `SMTP_WITH_AUTH`    |       -       | `0` or `1`                           |  yes   |
| `SMTP_USER`         |       -       | Required if auth is enabled          |  yes   |
| `SMTP_PASSWORD`     |       -       | Required if auth is enabled          |  yes   |

### Logging

| NAME                      | DEFAULT VALUE | INFO                                                                           |
|:--------------------------|:-------------:|:-------------------------------------------------------------------------------|
| `LOG_LEVEL`               |   `warning`   | Uses [RFC 5424](https://datatracker.ietf.org/doc/html/rfc5424) severity levels |
| `LOG_ENABLE_STACKTRACE`   |      `0`      | Only needed for debugging                                                      |
| `LOG_ENABLE_FILE_LOGGING` |      `1`      | Persist logs to a file in directory `storage/logs`                             |
