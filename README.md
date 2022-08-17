## Movary

Web application to track and rate your watched movies.

Demo installation can be found [here](https://movary-demo.leepeuker.dev/) (login with user `movary@movary.com` and password `movary`)

**Please report all bugs, improvement suggestions or feature wishes by creating [github issues](https://github.com/leepeuker/movary/issues)!**

1. [About](#install-via-docker)
2. [Install via docker](#install-via-docker)
3. [Important: First steps](#important-first-steps)
4. [Features](#features)
    1. [Tmdb Sync](#tmdb-sync)
    2. [Plex Scrobbler](#plex-scrobbler)
    3. [trakt.tv Import](#trakttv-import)
    3. [trakt.tv Export](#trakttv-export)
    4. [letterboxd.com Import](#letterboxd-import)
    4. [IMBb.com Sync](#imdb-sync)
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

You must provide a tmdb api key (see https://www.themoviedb.org/settings/api)

Example with an existing mysql server:

```shell
docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e DATABASE_HOST="<host>" \
  -e DATABASE_USER="<user>" \
  -e DATABASE_PASSWORD="<password>" \
  -e TMDB_API_KEY="<tmdb_key>" \
  leepeuker/movary:latest
```

Example with docker-compose.yml

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
```

<a name="#important-first-steps"></a>

## Important: First steps

- Run database migrations: `docker exec movary php bin/console.php database:migration:migrate`
- Create initial user: 
  - via web ui by visiting movary landingpage `/`
  - via cli `docker exec movary php bin/console.php user:create email@example.com password username`

List all available cli commands: `docker exec movary php bin/console.php`

##### Available environment variables with defaults:

```
DATABASE_HOST=
DATABASE_PORT=3306
DATABASE_NAME=movary
DATABASE_USER=
DATABASE_PASSWORD=
DATABASE_DRIVER=pdo_mysql
DATABASE_CHARSET=utf8

# Minimum number of seconds the job processing worker has to run 
# => the smallest possible timeperiode between processing two jobs
MIN_RUNTIME_IN_SECONDS_FOR_JOB_PROCESSING=15

# https://www.themoviedb.org/settings/api
TMDB_API_KEY= 

TIMEZONE="Europe/Berlin"

LOG_FILE="tmp/app.log"
LOG_LEVEL=warning
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

<a name="#plex-scrobbler"></a>

### Plex Scrobbler

Automatically track movies watched in plex with movary.

You can generate your plex webhook url on the settings page (`/settings`).

Add the generated url as a [webhook to plex](https://support.plex.tv/articles/115002267687-webhooks/).

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
