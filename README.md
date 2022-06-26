## Movary

Web app to log and rate watched movies.

1. [About](#install-via-docker)
2. [Install via docker](#install-via-docker)
3. [Important: First steps](#important-first-steps)
4. [Features](#features)
   1. [Plex Scrobbler](#plex-scrobbler)
   2. [Trakt.tv Sync](#trakttv-sync)
   3. [Tmdb Sync](#tmdb-sync)
5. [Development](#development)

<a name="#about"></a>
## About
This is a web app to log and rate watched movies (like a digital movie diary).

It was created because I wanted a self hosted solution instead of using external providers like trakt.tv or letterboxd and I wanted the focus to be on MY watch history (-> no social media features).

**Features:**
- add or update movie watch dates and ratings (only possible when logged in)
- statistics about your watched movies (e.g. most watched actors, most watched directors, most watched genres etc)
- uses themoviedb.org API for movie (meta) data
- import watched movies and ratings from trakt.tv and/or letterboxd.com
- connect with plex.tv to automatically log watched movies (plex premium required)

**Disclaimer:** This project is still in an experimental (but imo usable) state. I am planning to add more and improve existing features before creating a 1.0 realease.

<a name="#install-via-docker"></a>
## Install via docker

You must provide a tmdb api key (see https://www.themoviedb.org/settings/api)

Example (requires an existing mysql server + database to work):

```
docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e DATABASE_HOST="<host>" \
  -e DATABASE_USER="<user>" \
  -e DATABASE_PASSWORD="<password>" \
  -e TMDB_API_KEY="<tmdb_key>" \
  leepeuker/movary:latest
```

<a name="#important-first-steps"></a>
## Important: First steps

- After starting the movary container execute database migrations: `docker exec movary php bin/console.php movary:database:migration --migrate`
- The **default password** for the application is: `movary`
- You can **update the password** with: `docker exec movary php bin/console.php movary:change-admin-password <new_password>`

##### Available environment variables with defaults:

```
DATABASE_HOST=
DATABASE_PORT=3306
DATABASE_NAME=movary
DATABASE_USER=
DATABASE_PASSWORD=
DATABASE_DRIVER=pdo_mysql
DATABASE_CHARSET=utf8

# https://www.themoviedb.org/settings/api
TMDB_API_KEY= 

# https://trakt.tv/oauth/applications
TRAKT_USERNAME=
TRAKT_CLIENT_ID=

TIMEZONE="Europe/Berlin"

LOG_FILE="tmp/app.log"
LOG_LEVEL=warning
``` 

More configuration can be done via the base image webdevops/php-nginx, checkout their [docs](https://dockerfile.readthedocs.io/en/latest/content/DockerImages/dockerfiles/php-nginx.html) for more.

<a name="#features"></a>
## Features

<a name="#plex-scrobbler"></a>
### Plex Scrobbler

Automatically log movies watched in plex to movary.
Just add the following url as a [webhook](https://support.plex.tv/articles/115002267687-webhooks/) to plex, e.g. `http://127.0.0.1/plex`

<a name="#trakttv-sync"></a>
### trakt.tv sync

You can sync your watchhistory and ratings from trakt.tv.
Make sure you have added the variables `TRAKT_USERNAME` and `TRAKT_CLIENT_ID` to the environment.

Example:

`docker exec movary php bin/console.php movary:sync-trakt --ratings --history`

**Flags:**

- `--ratings`
  Sync trakt ratings
- `--history`
  Sync trakt watch history (plays)
- `--overwrite`
  Use if you want to overwrite the local state with the trakt state (deletes and overwrites local data)
- `--ignore-cache`
  Use if you want to sync everything from trakt regardless if there was a change since the last sync.

<a name="#tmdb-sync"></a>
### tmdb sync

Update movie (meta) data with themoviedb.org information.
Make sure you have added the variables `TMDB_API_KEY` to the environment.

Example:

`docker exec movary php bin/console.php movary:sync-tmdb`

**Flags:**

- `--hours`
  Only movies which were last synced X hours or longer ago will be synced
- `--threshold`
  Maximum number of movies to sync

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
