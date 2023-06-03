**Software requirements:**

- PHP 8.1
- git
- composer
- web server
- supervisor (optional)

```
git clone https://github.com/leepeuker/movary.git .
cp .env.production.example .env
composer install --no-dev
php bin/console.php storage:link
```

- Use the `.env` file to set the environment variables
- Setup web server host for php (`public` directory as document root)
- Make sure that the permissions on the `storage` directory are set correctly (the php should be able to write to it)
- Use supervisor to continuously process jobs, see `settings/supervisor/movary.conf` for an example config

Continue with [First steps](../first-steps.md)...
