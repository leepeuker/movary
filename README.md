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
Synchronize trakt data with the application. 

This sync includes:
- watch history
- movie rating

This sync will delete/overwrite all data included in the sync which does not match the current state of trakt. 
E.g. it will remove movie history entries not existing in trakt or change the existing rating of a movie.

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
