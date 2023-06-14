## Environment variables

| NAME                                        |      DEFAULT VALUE      | INFO                                                                    |
|:--------------------------------------------|:-----------------------:|:------------------------------------------------------------------------|
| `ENV`                                       |      `production`       |                                                                         |
| `TIMEZONE`                                  |    `"Europe/Berlin"`    | Supported timezones [here](https://www.php.net/manual/en/timezones.php) |
| `MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING` |          `15`           | Minimum time between job processings                                    |
| `DATABASE_MODE`                             |            -            | **Required** `sqlite` or `mysql`                                        |
| `DATABASE_SQLITE`                           | `storage/movary.sqlite` |                                                                         |
| `DATABASE_MYSQL_HOST`                       |            -            | Required when mode is `mysql`                                           |
| `DATABASE_MYSQL_PORT`                       |          3306           |                                                                         |
| `DATABASE_MYSQL_NAME`                       |            -            | Required when mode is `mysql`                                           |
| `DATABASE_MYSQL_USER`                       |            -            | Required when mode is `mysql`                                           |
| `DATABASE_MYSQL_PASSWORD`                   |            -            | Required when mode is `mysql`                                           |
| `DATABASE_MYSQL_CHARSET`                    |        `utf8mb4`        |                                                                         |
| `TMDB_API_KEY`                              |            -            | **Required** (get key [here](https://www.themoviedb.org/settings/api))  |
| `TMDB_ENABLE_IMAGE_CACHING`                 |           `0`           |                                                                         |
| `LOG_LEVEL`                                 |        `warning`        |                                                                         |
| `LOG_ENABLE_STACKTRACE`                     |           `0`           |                                                                         |
| `LOG_ENABLE_FILE_LOGGING`                   |           `1`           | Log directory is at `storage/logs`                                      |
| `ENABLE_REGISTRATION`                       |           `0`           | Enables public user registration                                        |
| `APPLICATION_URL`                           |            -            | Public base url of the application (e.g. `htttp://localhost`)           |
