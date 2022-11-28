## Movary

Web application to track and rate your watched movies.

Demo installation can be found [here](https://movary-demo.leepeuker.dev/) (login with user `movary@movary.com` and password `movary123`)

**Please report all bugs, improvement suggestions or feature wishes by creating [github issues](https://github.com/leepeuker/movary/issues)!**

1. [About](#install-via-docker)
2. [Install via docker](#install-via-docker)
3. [Important: First steps](#important-first-steps)
4. [Features](#features)
    1. [Tmdb Sync](#tmdb-sync)
    2. [Tmdb Image Cache](#tmdb-image-cache)
    3. [Plex Scrobbler](#plex-scrobbler)
    4. [trakt.tv Import](#trakttv-import)
    5. [trakt.tv Export](#trakttv-export)
    6. [letterboxd.com Import](#letterboxd-import)
    7. [IMBb Rating Sync](#imdb-sync)
5. [Development](#development)

<a name="#about"></a>

## About

This is a web application to track and rate your watched movies (like a digital movie diary).

It was created because I wanted a self hosted solution instead of using external providers like trakt.tv or letterboxd and I wanted the focus to be on my personal watch history (->
no
social media features).

It has support for multiple users accounts if you want to share it with friends.

**Features:**

- add or update movie watch dates and ratings (only possible when logged in)
- statistics about your watched movies (e.g. most watched actors, most watched directors, most watched genres etc)
- PWA: can be installed as an app ([How to install PWAs in chrome](https://support.google.com/chrome/answer/9658361?hl=en&co=GENIE.Platform%3DAndroid&oco=1))
- import watched movies and ratings from trakt.tv and letterboxd.com
- connect with plex via webhook to automatically log watched movies (plex premium required)
- uses themoviedb.org API for movie data
- export your personal data

**Disclaimer:** This project is still in an experimental (but imo usable) state. I am planning to add more and improve existing features before creating a 1.0 realease, which can
lead to breaking changes until then, so keep the release notes in mind when updating.

<a name="#install-via-docker"></a>

## Install via docker

This is the preferred and currently only tested way to run the app.

You must provide a tmdb api key (see https://www.themoviedb.org/settings/api)

Example with an existing mysql server:

```shell
$ docker volume create movary-storage

$ docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e DATABASE_HOST="<host>" \
  -e DATABASE_USER="<user>" \
  -e DATABASE_PASSWORD="<password>" \
  -e TMDB_API_KEY="<tmdb_key>" \
  -v movary-storage:/app/storage
  leepeuker/movary:latest
```

Example with docker-compose.yml with a mysql server

```yml
version: "3.5"

services:
  movary:
    image: leepeuker/movary:latest
    container_name: movary
    ports:
      - "80:80"
    environment:
      DATABASE_HOST: "mysql"
      DATABASE_NAME: "movary"
      DATABASE_USER: ""
      DATABASE_PASSWORD: ""
      TMDB_API_KEY: ""
    volumes:
      - movary-storage:/app/storage

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ""
      MYSQL_DATABASE: "movary"
      MYSQL_USER: ""
      MYSQL_PASSWORD: ""
    volumes:
      - movary-db:/var/lib/mysql

volumes:
  movary-db:
  movary-storage:
```

<a name="#important-first-steps"></a>

## Important: First steps

- Run database migrations, e.g.: `docker exec movary php bin/console.php database:migration:migrate`
- Create initial user:
    - via web ui by visiting movary landingpage `/`
    - via cli `docker exec movary php bin/console.php user:create email@example.com password username`

List all available cli commands: `docker exec movary php bin/console.php`

##### Available environment variables with defaults:

```
### Enviroment
ENV=production
TIMEZONE="Europe/Berlin"
# Minimum number of seconds the job processing worker has to run => the smallest possible timeperiode between processing two jobs
MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING=15

### Database
DATABASE_HOST=
DATABASE_PORT=3306
DATABASE_NAME=movary
DATABASE_USER=
DATABASE_PASSWORD=
DATABASE_DRIVER=pdo_mysql
DATABASE_CHARSET=utf8

### TMDB 
# https://www.themoviedb.org/settings/api
TMDB_API_KEY= 
# Save and deliver movie/person posters locally
TMDB_ENABLE_IMAGE_CACHING=0

### Plex 
# https://app.plex.tv/desktop/#!/settings/webhooks
PLEX_ENABLE_SCROBBLE=1
PLEX_ENABLE_RATING=0

### Logging
LOG_LEVEL=warning
LOG_ENABLE_STACKTRACE=0
LOG_ENABLE_FILE_LOGGING=0
``` 

More configuration can be done via the base image webdevops/php-nginx, checkout
their [docs](https://dockerfile.readthedocs.io/en/latest/content/DockerImages/dockerfiles/php-nginx.html) for more.

<a name="#features"></a>

## Features

<a name="#tmdb-sync"></a>

### tmdb sync

Update movie (meta) data with themoviedb.org information.
Make sure you have added the variables `TMDB_API_KEY` to the environment.

Example:

`docker exec movary php bin/console.php tmdb:sync`

**Flags:**

- `--hours`
  Only movies which were last synced X hours or longer ago will be synced
- `--threshold`
  Maximum number of movies to sync

<a name="#tmdb-image"></a>

### tmdb image cache

To e.g. prevent rate limit issues with the TMDB api you should cache tmdb images (movie/person posters) with movary.
This will store a local copy of the image in the `storage` directory.
Make sure you persist the content of the `storage` directory to keep data e.g. when restarting docker container.

Enable by setting environment variable `TMDB_ENABLE_IMAGE_CACHING` to `1`.

This will activate:

- using locally cached image path for image urls (if existing) instead of tmdb.org url
- cache new images automatically when adding/updating movie/person meta data

Helpful commands:

- Refresh image cache: `php bin/console.php tmdb:imageCache:refresh`
- Delete cached images: `php bin/console.php tmdb:imageCache:delete`

<a name="#plex-scrobbler"></a>

### Plex Scrobbler

Automatically track movies watched in plex with movary.

You can generate your plex webhook url on the apps settings page (`/settings`).

Add the generated url as a [webhook to plex](https://support.plex.tv/articles/115002267687-webhooks/).

As a default only your watches are tracked, but you can additionally enable the tracking of your movie ratings.

<a name="#trakttv-import"></a>

### Trakt.tv Import

You can import your watch history and ratings from trakt.tv (exporting from movary to trakt not supported yet).

The user used in the import process must have a trakt username and client id set (can be set via settings page `/settings/trakt` or via cli `user:update`).

The import can be executed via the settings page `/settings/trakt` or via cli.

Example cli import (import history and ratings for user with id 1):

`docker exec movary php bin/console.php trakt:import --ratings --history --userId=1`

**Flags:**

- `--userId`
  User to import data to
- `--ratings`
  Import trakt ratings
- `--history`
  Import trakt watch history (plays)
- `--overwrite`
  Use if you want to overwrite the local state with the trakt state (deletes and overwrites local data)
- `--ignore-cache`
  Use if you want to import everything from trakt regardless if there was a change since the last import.

<a name="#trakttv-export"></a>

### Trakt.tv Export

coming soon ([maybe](https://github.com/leepeuker/movary/issues/97)?)

<a name="#letterboxd-import"></a>

### Letterboxd.com Import

You can import your watch history and ratings from letterboxd.com.

Visit the movary settings page `/settings/letterboxd` for more instructions

<a name="#imdb-sync"></a>

### IMDb Sync

Sync imdb ratings.

Example:

`docker exec movary php bin/console.php imdb:sync`

**Flags:**

- `--hours`
  Only movie ratings which were last synced X hours or longer ago will be synced
- `--threshold`
  Maximum number of movie ratings to sync

<a name="#development"></a>

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
