# Movary

## Install

### Prerequisite
- trakt account and api credentials (https://trakt.tv/oauth/applications) 
- tmdb api key (https://www.themoviedb.org/settings/api)
- mysql 5.7 or higher
- Docker (20.10.14) OR php 8.1 + composer installed

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

## Useful links:
- Trakt API docs: https://trakt.docs.apiary.io/
- TMDB API docs: https://developers.themoviedb.org/3
