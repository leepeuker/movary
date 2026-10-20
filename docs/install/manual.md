# Manual installation

A manual installation runs Movary directly on a server. The [Docker installation](docker.md) is recommended unless you need to manage the web server, PHP runtime, and background worker yourself.

## System requirements

- PHP 8.5 with PHP-FPM and these extensions:
    - ctype
    - cURL
    - JSON
    - mbstring
    - OpenSSL
    - PDO and either the MySQL or SQLite PDO driver
    - SQLite3 when using SQLite
    - ZIP
    - zlib
- Git
- [Composer](https://getcomposer.org)
- A web server such as nginx
- Supervisor or cron for background job processing

Package names vary by operating system. On Alpine Linux, the PHP packages include `php85-ctype`, `php85-curl`, `php85-mbstring`, `php85-openssl`, `php85-pdo`, `php85-pdo_mysql` or `php85-pdo_sqlite`, `php85-sqlite3` when using SQLite, and `php85-zip`; JSON and zlib are included in PHP itself.

## Required configuration

Prepare these settings before starting Movary:

| Setting | Requirement |
|:--------|:------------|
| `APPLICATION_SECRET` | Required. Generate it with `openssl rand -hex 32`, then keep it stable across restarts and identical across all Movary instances. |
| `TMDB_API_KEY` | Required for movie metadata. Get a key from [TMDB](https://www.themoviedb.org/settings/api). It can also be configured in the Web UI after startup. |
| `APPLICATION_URL` | Recommended. Set it to the public URL, such as `https://movary.example.com`. It is required for email verification and password resets. |
| `TIMEZONE` | Optional. Defaults to `UTC`; use a [supported PHP timezone](https://www.php.net/manual/en/timezones.php). |

SQLite is used by default. MySQL additionally requires `DATABASE_MODE=mysql`, `DATABASE_MYSQL_HOST`, `DATABASE_MYSQL_NAME`, `DATABASE_MYSQL_USER`, and `DATABASE_MYSQL_PASSWORD`. See [Configuration](../configuration.md) for every available setting.

!!! warning

    Do not change `APPLICATION_SECRET` after setup. Movary uses it to derive cryptographic keys.

## Install Movary

The commands below use `/var/www/movary` as the installation directory. Adjust the path and service user for your server.

1. Clone the repository:

    ```shell
    git clone https://github.com/leepeuker/movary.git /var/www/movary
    cd /var/www/movary
    ```

2. Install the PHP dependencies:

    ```shell
    composer install --no-dev
    ```

3. Create the environment file and application secret:

    ```shell
    cp .env.example .env
    openssl rand -hex 32
    ```

    Edit `.env`, set the generated value as `APPLICATION_SECRET`, and add the other required configuration described above.

4. Give the PHP-FPM user read and write access to `storage`. For example, when PHP-FPM runs as `www-data`:

    ```shell
    chown -R www-data:www-data storage
    ```

5. Create the public storage link:

    ```shell
    php bin/console.php storage:link
    ```

6. Run the database migrations:

    ```shell
    php bin/console.php database:migration:migrate
    ```

Run `composer install --no-dev` and the migration command again after every update. Back up the database and `storage` directory before updating Movary.

## Configure the web server

Set Movary's `public` directory as the web server document root. The repository's `build/config/conf.d/default.conf` file provides an example nginx configuration.

Use HTTPS for an internet-facing installation and set `APPLICATION_URL` to the matching public `https://` URL.

## Job processing

The `jobs:process` command keeps Movary data up to date and processes background work, including verification and password reset emails. Run it continuously with Supervisor or at least once per minute with cron.

### Supervisor

This example assumes Movary is installed in `/var/www/movary` and PHP-FPM runs as `www-data`:

```ini
[program:movary]
command=/usr/local/bin/php /var/www/movary/bin/console.php jobs:process
directory=/var/www/movary
numprocs=1
user=www-data
autostart=true
autorestart=true
startsecs=1
startretries=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

Adjust the PHP binary, installation directory, and user for your server.

### Cron

Run the worker once per minute:

```cron
* * * * * cd /var/www/movary && /usr/local/bin/php bin/console.php jobs:process
```

## Verify the installation

Check the migration status and run the worker once:

```shell
php bin/console.php database:migration:status
php bin/console.php jobs:process
```

Open `APPLICATION_URL` in a browser and continue with [Getting started](../getting-started.md).
