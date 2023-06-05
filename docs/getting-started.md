You can run movary commands via `php bin/console.php` (executing this will list all available commands).

1. Create initial user
    - via web UI by visiting the movary lading page for the first time
    - via cli `php bin/console.php user:create email@example.com password username`
2. Check the `/settings` page to customize movary like you want

It is recommended to enable tmdb image caching (set env variable `TMDB_ENABLE_IMAGE_CACHING=1`).
