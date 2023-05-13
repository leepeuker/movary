# Movary

[![Docker pulls badge](https://img.shields.io/docker/pulls/leepeuker/movary)](https://hub.docker.com/repository/docker/leepeuker/movary)
[![GitHub issues badge](https://img.shields.io/github/issues/leepeuker/movary)](https://github.com/leepeuker/movary/issues)
[![Reddit badge](https://img.shields.io/reddit/subreddit-subscribers/movary)](https://www.reddit.com/r/movary/)
[![License badge](https://img.shields.io/github/license/leepeuker/movary)](https://github.com/leepeuker/movary/blob/main/LICENSE)
[![Matrix](https://upload.wikimedia.org/wikipedia/commons/thumb/7/7c/Matrix_icon.svg//18px-Matrix_icon.svg.png)](https://matrix.to/#/#movary:leepeuker.dev)

Movary is a self-hosted web application to track and rate your watched movies (like a digital movie diary).
You can import/export your history and ratings from/to third parties like trakt.tv or letterboxd.com,
scrobble your watches via Plex/Jellyfin/Emby and more.

Demo installation can be found [here](https://demo.movary.org/) (login email `testUser@movary.org` and password `testUser`).

![Movary Dashboard Example](https://i.imgur.com/1qvhWFh.png)

1. [About](#about)
2. [Install](#install)
    1. [With Docker (recommended)](#install-with-docker-recommended)
    2. [Without Docker](#install-without-docker)
3. [Important: First steps](#important-first-steps)
4. [Features](#features)
    1. [Tmdb Sync](#tmdb-sync)
    2. [Tmdb Image Cache](#tmdb-image-cache)
    3. [Plex Scrobbler](#plex-scrobbler)
    4. [Jellyfin Scrobbler](#jellyfin-scrobbler)
    5. [Emby Scrobbler](#emby-scrobbler)
    6. [trakt.tv Import](#trakttv-import)
    7. [Letterboxd.com Import](#letterboxdcom-import)
    8. [Letterboxd.com Export](#letterboxdcom-export)
    9. [Netflix Import](#netflix-import)
    10. [IMDb Rating Sync](#imdb-rating-sync)
5. [FAQ](#faq)
6. [Development](#development)
7. [Support](#support)
8. [Contributors](#contributors)

Please report all bugs, improvement suggestions or feature wishes by creating [github issues](https://www.reddit.com/r/movary/)
or visit the [official subreddit](https://www.reddit.com/r/movary/)!

---

## About

This project started because I wanted a self-hosted solution for tracking my watched movies and their ratings,
so that I can really own my data and do not have to solely rely on other providers like letterboxd
or trakt to keep it safe (or decide what to do with it).

**Features:**

- Movie tracking: Collect and manage your watch history and ratings
- Statistics: Overview over your movie watching behavior and history, like e.g. most watched actors/directors/genres/languages/years
- Third party support: Import and export your history and ratings from/to third partys like letterboxd.com or trakt.tv
- Plex/Jellyfin/Emby scrobbler: Automatically add new watches and ratings to Movary
- Own your personal data: Users can decide who can see their data and export/import/delete the data and their accounts at any time
- Locally stored metadata: Using e.g. themoviedb.org and imdb as sources, all metadata movary uses for your history entries can be stored locally
- PWA: Can be installed as an app ([How to install PWAs in chrome](https://support.google.com/chrome/answer/9658361?hl=en&co=GENIE.Platform%3DAndroid&oco=1))
- Completely free, no ads, no tracking and open source! :)

Movary has support for multiple users accounts in case you want to share your instance, but was designed with only a small number of accounts in mind.

**Disclaimer:** This project is still in an experimental (but imo completely usable) state. I am planning to add more and improve existing features before creating a 1.0 realease,
which can lead to sudden breaking changes until then, so keep the release notes in mind when updating.

## Install

### Install with docker (recommended)

This is the recommended way to run the app.

You must provide a tmdb api key (get one [here](https://www.themoviedb.org/settings/api)).

Example using SQLite:

```shell
$ docker volume create movary-storage
$ docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e TMDB_API_KEY="<tmdb_key>" \
  -e DATABASE_MODE="sqlite" \
  -v movary-storage:/app/storage \
  leepeuker/movary:latest
```

Example using MySQL:

```shell
$ docker volume create movary-storage
$ docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e TMDB_API_KEY="<tmdb_key>" \
  -e DATABASE_MODE="mysql" \
  -e DATABASE_MYSQL_HOST="<host>" \
  -e DATABASE_MYSQL_NAME="<db_name>" \
  -e DATABASE_MYSQL_USER="<db_user>" \
  -e DATABASE_MYSQL_PASSWORD="<db_password>" \
  -v movary-storage:/app/storage \
  leepeuker/movary:latest
```

Example docker-compose.yml with a MySQL server

```yml
version: "3.5"

services:
  movary:
    image: leepeuker/movary:latest
    container_name: movary
    ports:
      - "80:80"
    environment:
      TMDB_API_KEY: "<tmdb_key>"
      DATABASE_MODE: "mysql"
      DATABASE_MYSQL_HOST: "mysql"
      DATABASE_MYSQL_NAME: "movary"
      DATABASE_MYSQL_USER: "movary_user"
      DATABASE_MYSQL_PASSWORD: "movary_password"
    volumes:
      - movary-storage:/app/storage

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: "movary"
      MYSQL_USER: "movary_user"
      MYSQL_PASSWORD: "movary_password"
      MYSQL_ROOT_PASSWORD: "<mysql_root_password>"
    volumes:
      - movary-db:/var/lib/mysql

volumes:
  movary-db:
  movary-storage:
```

Continue with [Important: First steps](#important-first-steps)

### Install without docker

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

Continue with [Important: First steps](#important-first-steps)

## Important: First steps

You can run movary commands via `php bin/console.php`

1. Execute missing database migrations: `php bin/console.php database:migration:migrate` (on **initial installation** and ideally **after every update**)
2. Create initial user
    - via web UI by visiting the movary lading page for the first time
    - via cli `php bin/console.php user:create email@example.com password username`
3. Check the `/settings` page to customize movary like you want

It is recommended to enable tmdb image caching (set env variable `TMDB_ENABLE_IMAGE_CACHING=1`).

##### Available environment variables with their default values:

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
| `APPLICATION_URL`                           |            -            | Base url of the application (e.g. `htttp://localhost`)                  |

More configuration can be done via the base image webdevops/php-nginx, checkout
their [docs](https://dockerfile.readthedocs.io/en/latest/content/DockerImages/dockerfiles/php-nginx.html) for more.

## Features

Use `php bin/console.php` to list all available cli commands

### tmdb sync

Update movie or person meta data with themoviedb.org information.
Make sure you have added the variables `TMDB_API_KEY` to the environment.

Helpful commands:

`php bin/console.php tmdb:movie:sync` -> Refresh local movie meta data

`php bin/console.php tmdb:person:sync` -> Refresh local person meta data

**Interesting flags:**

- `--hours`
  Only update movies/persons which were last synced X hours or longer ago
- `--threshold`
  Maximum number of movies/person to sync for this run

### tmdb image cache

Enable by setting environment variable `TMDB_ENABLE_IMAGE_CACHING` to `1`.

To e.g. prevent rate limit issues with the TMDB api you should cache tmdb images (movie/person posters) with movary.
This will store a local copy of the image in the `storage` directory and serve this image instead of the original one from TMDB.
Make sure you persist the content of the `storage` directory to keep data e.g. when restarting docker container.

Execute the cache refresh command regularly, e.g. via cronjob, to keep the cache up to date.

Helpful commands:

- `php bin/console.php tmdb:imageCache:refresh` -> Refresh local image cache
- `php bin/console.php tmdb:imageCache:delete` -> Delete locally cached images

### Plex Scrobbler

Automatically track movies watched via Plex with Movary ([Plex Pass](https://www.plex.tv/plex-pass/) required!).

You can generate your webhook url on the Plex integration settings page (`/settings/integrations/plex`).

Add the generated url as a [webhook to plex](https://support.plex.tv/articles/115002267687-webhooks/) to start scrobbling!

You can select what you want movary to scrobble (movie views and/or ratings) via the "Scrobble Options" checkboxes on the settings page.

### Jellyfin Scrobbler

Automatically track movies watched via Jellyin with Movary.

You can generate your webhook url on the Jellyfin integration settings page (`/settings/integrations/jellyfin`) and configure it in Jellyfin via
the [webhook plugin](https://github.com/jellyfin/jellyfin-plugin-webhook).

### Emby Scrobbler

Automatically track movies watched via Emby with Movary ([Emby Premiere](https://emby.media/premiere.html) required!).

You can generate your webhook url on the Emby integration settings page (`/settings/integrations/emby`). Add the generated url as
a [webhook](https://emby.media/support/articles/Webhooks.html) in Emby.

### Trakt.tv Import

You can import your watch history and ratings from trakt.tv (exporting from movary to trakt not supported yet).

The trakt account used in the import process must have a trakt username and client id set (can be set via settings page `/settings/trakt` or via cli `user:update`).

The import can be executed via the settings page `/settings/trakt` or via cli.

Example cli import (import history and ratings for user with id 1 and overwrite locally existing data if needed):

`php bin/console.php trakt:import --userId=1 --ratings --history --overwrite`

**Info:** Importing hundreds or thousands of movies for the first time can take a few minutes.

**Interesting flags:**

- `--userId`
  User to import data to
- `--ratings`
  Import trakt ratings
- `--history`
  Import trakt watch history (plays)
- `--overwrite`
  Use if you want to overwrite the local data with the data coming from trakt
- `--ignore-cache`
  Use if you want to force import everything regardless if there was a change since the last import

### Letterboxd.com Import

You can import your watch history and ratings from letterboxd.com.

Visit the movary settings page `/settings/letterboxd` for more instructions.

**Info:** Importing hundreds or thousands of movies for the first time can take a few minutes.

### Letterboxd.com Export

You can export your local watch history and ratings to letterboxd.com.

Visit the movary settings page `/settings/letterboxd` for more instructions.

### Netflix Import

You can import your watch history from netflix.

Visit the movary settings page `/settings/netflix` for more instructions.

### IMDb Rating Sync

Sync ratings from imdb for local movies.

Example:

`php bin/console.php imdb:sync`

**Flags:**

- `--hours`
  Only sync movie ratings which were last synced at least X hours ago
- `--threshold`
  Maximum number of movie ratings to sync

## FAQ

Q: Will Movary support tv shows or other media types?

A: Currently there is no active development for supporting more media types. Contributions in that directions are welcome!

---

Q: Can I share my history and ratings publicly?

A: Yes, you can set (e.g. via `/settings` page) your `Privacy` levels and decide who is allowed to view your data. All page urls starting with `/users/<username>/...` (= pages with
a user selector at the top) can be set to be publicly visible.

## Development

### Setup

Clone the repository and follow these steps for a local development setup:

- run `cp .env.development.example .env` and edit the `.env` file content
- run `make build` to build the containers and set up the application
- run `make up` to start the containers

The application should be up-to-date and running locally now.

### Useful links:

- Trakt API docs: https://trakt.docs.apiary.io/
- TMDB API docs: https://developers.themoviedb.org/3

## Support

- Join the matrix [support chat](https://matrix.to/#/#movary-support:leepeuker.dev)
- Report bugs or request features via github [issues](https://github.com/leepeuker/movary/issues)
- Ask questions or discuss movary related topics in the [official subreddit](https://www.reddit.com/r/movary/)

## Contributors

* [@leepeuker](https://github.com/leepeuker) as Lee Peuker
* [@JVT038](https://github.com/JVT038) as JVT038
