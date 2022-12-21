# Movary

[![Docker pulls badge](https://img.shields.io/docker/pulls/leepeuker/movary)](https://hub.docker.com/repository/docker/leepeuker/movary)
[![GitHub issues badge](https://img.shields.io/github/issues/leepeuker/movary)](https://github.com/leepeuker/movary/issues)
[![Reddit badge](https://img.shields.io/reddit/subreddit-subscribers/movary)](https://www.reddit.com/r/movary/)
[![License badge](https://img.shields.io/github/license/leepeuker/movary)](https://github.com/leepeuker/movary/blob/main/LICENSE)

Movary is a self-hosted web application to track and rate your watched movies (like a digital movie diary).
You can import/export your history and ratings from/to external sources like trakt.tv or letterboxd.com,
track your watches automatically via plex and more.

Demo installation can be found [here](https://demo.movary.org/) (login email `testUser@movary.org` and password `testUser`).

![Movary Dashboard Example](https://i.imgur.com/690Rr80.png)

1. [About](#about)
2. [Install via docker](#install-via-docker)
3. [Important: First steps](#important-first-steps)
4. [Features](#features)
    1. [Tmdb Sync](#tmdb-sync)
    2. [Tmdb Image Cache](#tmdb-image-cache)
    3. [Plex Scrobbler](#plex-scrobbler)
    4. [trakt.tv Import](#trakttv-import)
    5. [trakt.tv Export](#trakttv-export)
    6. [Letterboxd.com Import](#letterboxdcom-import)
    7. [IMDb Rating Sync](#imdb-rating-sync)
5. [Development](#development)
6. [Support](#support)

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
- Third party support: Import your existing history and ratings from e.g. trakt.tv or letterboxd.com
- Plex scrobbler: Automatically add new plex watches and ratings (plex premium required)
- Own your personal data: Users can decide who can see their data and export/import/delete the data and their accounts at any time
- Locally stored metadata: Using e.g. themoviedb.org and imdb as sources, all metadata movary uses for your history entries can be stored locally
- PWA: Can be installed as an app ([How to install PWAs in chrome](https://support.google.com/chrome/answer/9658361?hl=en&co=GENIE.Platform%3DAndroid&oco=1))
- Completely free, no ads, no tracking and open source! :)

Movary has support for multiple users accounts in case you want to share your instance, but was designed with only a small number of accounts in mind.

**Disclaimer:** This project is still in an experimental (but imo completely usable) state. I am planning to add more and improve existing features before creating a 1.0 realease,
which can lead to sudden breaking changes until then, so keep the release notes in mind when updating.

<a name="#link-install-via-docker"></a>

## Install via docker

This is the preferred and currently only tested way to run the app.

You must provide a tmdb api key (get one [here](https://www.themoviedb.org/settings/api)).

Example default (using sqlite):

```shell
$ docker volume create movary-storage

$ docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e DATABASE_MODE="sqlite" \
  -e TMDB_API_KEY="<tmdb_key>" \
  -v movary-storage:/app/storage
  leepeuker/movary:latest
```

Example docker-compose.yml with a mysql server

```yml
version: "3.5"

services:
  movary:
    image: leepeuker/movary:latest
    container_name: movary
    ports:
      - "80:80"
    environment:
      TMDB_API_KEY: "XXX"
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
      MYSQL_ROOT_PASSWORD: "XXX"
    volumes:
      - movary-db:/var/lib/mysql

volumes:
  movary-db:
  movary-storage:
```

## Important: First steps

You can run movary commands in docker via e.g. `docker exec movary php bin/console.php`

1. Execute all missing database migrations, e.g. like this: `php bin/console.php database:migration:migrate` (on initial installation and after every update)
2. Create initial user
    - via web UI by visiting the movary lading page for the first time
    - via cli `php bin/console.php user:create email@example.com password username`

It is recommended to enable tmdb image caching (set env variable `TMDB_ENABLE_IMAGE_CACHING=1`).

##### Available environment variables with defaults:

```
### Enviroment
ENV=production
TIMEZONE="Europe/Berlin"
# Minimum number of seconds the job processing worker has to run => the smallest possible timeperiode between processing two jobs
MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING=15

### Database
# Supported modes: sqlite or mysql
DATABASE_MODE=sqlite
DATABASE_SQLITE=storage/movary.sqlite
DATABASE_MYSQL_HOST=
DATABASE_MYSQL_PORT=
DATABASE_MYSQL_NAME=
DATABASE_MYSQL_USER=
DATABASE_MYSQL_PASSWORD=
DATABASE_MYSQL_CHARSET=utf8mb4

### TMDB 
# Used for metda data collection, see: https://www.themoviedb.org/settings/api
TMDB_API_KEY= 
# Save and deliver movie/person posters locally
TMDB_ENABLE_IMAGE_CACHING=0

### Logging
LOG_LEVEL=
LOG_ENABLE_STACKTRACE=0
LOG_ENABLE_FILE_LOGGING=0
``` 

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

Automatically track movies watched in plex with movary.

You can generate your plex webhook url on the plex settings page (`/setting/plex`).

Add the generated url as a [webhook to plex](https://support.plex.tv/articles/115002267687-webhooks/) to start scrobbling!

You can select what you want movary to scrobble (movie views and/or ratings) via the "Scrobbler Options" checkboxes on the settings page.

### Trakt.tv

#### Import data

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
  Use if you want to overwrite the local state with the trakt state (deletes and overwrites local data)
- `--ignore-cache`
  Use if you want to force import everything regardless if there was a change since the last import

#### Export

Coming soon ([maybe](https://github.com/leepeuker/movary/issues/97)?)

### Letterboxd.com Import

You can import your watch history and ratings from letterboxd.com.

Visit the movary settings page `/settings/letterboxd` for more instructions

**Info:** Importing hundreds or thousands of movies for the first time can take a few minutes.

<a name="#link-imdb-sync"></a>

### IMDb Rating Sync

Sync ratings from imdb for local movies.

Example:

`php bin/console.php imdb:sync`

**Flags:**

- `--hours`
  Only sync movie ratings which were last synced at least X hours ago
- `--threshold`
  Maximum number of movie ratings to sync

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

- Report bugs or request features via github [issues](https://github.com/leepeuker/movary/issues)
- Ask questions or discuss movary related topics in the [official subreddit](https://www.reddit.com/r/movary/)
