## System requirements

- PHP 8.2
- git
- composer
- web server
- supervisor (optional but recommended)

## Setup

### Application

1. Clone the repository
```
git clone https://github.com/leepeuker/movary.git .
```

2. Install composer dependencies (execute after every update)
```
composer install --no-dev
```

3. Create (and edit) your environment configuration
```
cp .env.production.example .env
```

4. Create necessary symlink between the storage and public/storage
```
php bin/console.php storage:link
```

!!! Info

    Make sure that the permissions on the `storage` directory are correct and set to writable for the application user

### Web server

Use the `public` directory as the document root

### Supervisor
Use supervisor to continuously process jobs/events created by the application.

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
