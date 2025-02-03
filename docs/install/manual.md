## System requirements

- PHP 8.4 (FPM) + extensions:
    - php84-pdo
    - php84-pdo_mysql
    - php84-pdo_sqlite
    - php84-mbstring
    - php84-sqlite3
    - php84-simplexml
    - php84-pecl-imagick
- git
- [composer](https://getcomposer.org)
- web server (e.g. nginx)
- supervisor or cron for job processing

## Setup

### Application

1. Clone the repository
```
git clone https://github.com/leepeuker/movary.git .
```

2. Install composer dependencies (recommended to install after every update)
```
composer install --no-dev
```

3. Create (and edit) your environment configuration
```
cp .env.example .env
```

4. Create necessary symlink between the storage and public/storage
```
php bin/console.php storage:link
```

5. Run the database migrations
```
php bin/console.php database:migration:migrate
```

!!! Info

    Make sure that the permissions on the `storage` directory are correct and set to writable for the php (fpm) user

### Web server

Use the `public` directory as the document root

### Job processing

The `jobs:process` cli command has to be executed to keep the Movary data up to date and process all background jobs.

Here are two recommanded ways.

#### Supervisor
Supervisor manages a process to execute the cli command contentiously.  

Example config:

```
[program:movary]
command=/usr/local/bin/php /app/bin/console.php jobs:process
numprocs=1
user=movary
autostart=true
autorestart=true
startsecs=1
startretries=10
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
```

#### Cron
Use a cronjob to process jobs in at least 1 minute intervals.

Example config:

```
* * * * * usr/local/bin/php /app/bin/console.php jobs:process
```
