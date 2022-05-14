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

### app:sync-trakt

Synchronize remote trakt watch history (plays) with local state.

At default, this will do the following:

- only add new or increase the plays per watch date.
- only add new ratings.

It will not remove or update ratings existing locally.
It will not remove or decrease the plays per date.

You can only sync specific stuff by using flags (default behaviour syncs all of them if no flag is specified)

**Flags:**

- `-- ratings`
  Sync trakt ratings
- `-- history`
  Sync trakt watch history (plays)
- `-- overwrite`
  Use if you want to completely overwrite the local state with the remote (trakt) state (deletes and overwrites local data)

### app:sync-tmdb

Synchronize tmdb (meta-)data for existing movies.

This sync includes:

- movie details
- movie cast
- movie crew
- movie production company

## Useful links:

- Trakt API docs: https://trakt.docs.apiary.io/
- TMDB API docs: https://developers.themoviedb.org/3
