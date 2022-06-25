# Movary

This is a tool I have written to sync my movie watch history and ratings from trakt.tv to my personal server.

Additionally it enriches the synced movies with data provided by tmdb (e.g. movie metadata, credits, production companies).

## Install

### Prerequisite

- trakt account and api credentials (https://trakt.tv/oauth/applications)
- tmdb api key (https://www.themoviedb.org/settings/api)
- Docker (20.10.14) [for php and web server]
- mysql 8.0 [only included in docker in development setup]

### Install with docker

- `cp .env.example .env`
- set config values in .env
- `make build`

## Commands

### app:change-admin-password

```
php bin/console.php app:change-admin-password <password>
```

Change admin password to password provided by first argument.

### app:sync-trakt

```
php bin/console.php app:sync-trakt
```

Synchronize remote trakt watch history (plays) with local state.

At default, this will do the following:

- only add new or increase the plays per watch date.
- only add new ratings.

It will not remove or update ratings existing locally.
It will not remove or decrease the plays per date.

You can only sync specific stuff by using flags (default behaviour syncs all of them if no flag is specified)

**Flags:**

- `--ratings`
  Sync trakt ratings
- `--history`
  Sync trakt watch history (plays)
- `--overwrite`
  Use if you want to overwrite the local state with the trakt state (deletes and overwrites local data)
- `--ignore-cache`
  Use if you want to sync everything from trakt regardless if there was a change since the last sync.

### app:sync-tmdb

```
php bin/console.php app:sync-tmdb
```

Synchronize tmdb (meta-)data for existing movies. Without providing `--hours` flag, this will run the sync for all locally existing movies.

This sync includes:

- movie details
- movie cast
- movie crew
- movie production company

**Flags:**

- `--hours`
  Only movies which were not updated for at least this amount of hours will be synced again
- `--threshold`
  Maximum number of movies to sync for this run

## Development

### Setup

Follow the following steps for a local development setup:

- run `cp .env.development.example .env` and edit the `.env` file content
- run `make up` to start the docker-compose container
- run `make composer_install` to install composer dependencies
- run `make db_migration_migrate` to execute database migrations

The application should be up-to-date and running locally now.

### Useful links:

- Trakt API docs: https://trakt.docs.apiary.io/
- TMDB API docs: https://developers.themoviedb.org/3



https://support.plex.tv/articles/115002267687-webhooks/
