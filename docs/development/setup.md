To set up a basic local development environment follow these steps:

- run `cp .env.development.example .env` and edit the `.env` file to customize the local environment 
- run `make build` to build the docker containers using compose and run all necessary steps for an initial setup like
  - create the database
  - install composer dependencies
  - run database migrations
  - create the storage symlink

The application should be up-to-date and running locally now. 

Use the following cli commands to manage your local environment:

- run `make down` to stop all containers
- run `make up` to start all containers again (`build` is only for the initial setup!)
- run `make reup` to stop and restart all containers
- run `make reup` to stop and restart all containers
- run `make app_database_migrate` execute the database migrations
- run `make app_jobs_process` process the next job from the queue (see database table `job_queue`)

### Api docs

Checkout the API docs via the url path `{your-movie-url}/docs/api`.

This uses the schema defined in the file `/docs/openapi.json`

### Useful links

- [Trakt API docs](https://trakt.docs.apiary.io/)
- [TMDB API docs](https://developers.themoviedb.org/3)
