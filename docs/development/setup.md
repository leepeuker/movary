### Local development setup

To set up a basic local development environment follow these steps:

- clone the repository
- copy the file `.env.development.example` to `.env` and customize it for your local environment
    - make sure the `USER_ID` matches your UID (`echo $UID`)
    - adjust `DOCKER_COMPOSE_BIN` if you have the legacy docker-compose binary
    - add your `TMDB_API_KEY`
- run `make build` to build the docker containers and to run all necessary steps for an initial setup like
    - create the database
    - install composer dependencies
    - run database migrations
    - create the storage symlink

The application should be up-to-date and running locally now.

Use the following cli commands to manage your local environment:

- run `make up` to start all containers again (`build` is only for the initial setup!)
- run `make down` to stop all containers
- run `make reup` to stop and restart all containers
- run `make app_database_migrate` execute the database migrations
- run `make app_jobs_process` process the next job from the queue (see database table `job_queue`)

### IDE recommendation: PhpStorm

We recommend to use PhpStorm and to import the Movary code style scheme (found at `settings/phpstorm.xml`).
For import instructions see the [official docs](https://www.jetbrains.com/help/phpstorm/configuring-code-style.html#import-export-schemes).

To apply the code style rules use at least the following features:

- `Reformat code` (more info [here](https://www.jetbrains.com/help/phpstorm/rearrange-code.html))
- `Rearrange code` (more info [here](https://www.jetbrains.com/help/phpstorm/rearrange-code.html))
- `Optimize imports`

Notes:

- Please apply the code style rules for every file you changed
- To find the default shortcuts for the features and/or customize search for them in Settings -> Keymap
- If you use the PhpStorm UI for git you can execute the features automatically before every commit (Settings -> Version Control -> Commit -> Commit Checks)

### Documentation

#### General

We use [Material for MkDocs](https://squidfunk.github.io/mkdocs-material/) for the documentation.

This is part of the default development docker compose setup and can be reached via `http://127.0.0.1:8000`.

To adjust the documentation files look into the `docs` directory and the configuration of MkDocs is in `mkdocs.yml`. 

#### Api

Checkout the API docs via the url `http://127.0.0.1/docs/api`.

This uses the schema defined in the file `/docs/openapi.json`. Please adjust openapi schema if you change the API.

### Useful links

- [Trakt API docs](https://trakt.docs.apiary.io/)
- [TMDB API docs](https://developers.themoviedb.org/3)
- [OpenAPI API docs](https://swagger.io/docs/specification/about/)
