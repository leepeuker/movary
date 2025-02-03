### Local development setup

First steps for local development setup:

- Clone the repository
- Copy the file `.env.example` to `.env` and customize it for your local environment
    - Set `USER_ID` to the UID owning the local files (`echo $UID`)
    - Add your `TMDB_API_KEY`
- Run `make build_development` to create your local development environment 
    - Build and start the development stage of the docker image from scratch
    - Mount project files in to the docker container (changes to files affect application in realtime)
    - Install composer dependencies
    - Create the database (sqlite on default)
    - Run database migrations
    - Create the storage symlink

The application should be up-to-date and running locally now.

Use the following cli commands to manage your local environment:

- `make up` to start the application in production stage using SQLite (using docker volumes)
- `make up_mysql` to start the application in production stage using MySQL (using docker volumes)
- `make up_development` to start the application in development stage using SQLite (mounting local files)
- `make up_development_myqsl` to start the application in development stage using MySQL (mounting local files)
- `make down` to stop all containers
- `make app_database_migrate` execute the database migrations
- `make app_jobs_process` process the next job from the queue (see database table `job_queue`)

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

##### Description

We use [Material for MkDocs](https://squidfunk.github.io/mkdocs-material/) for the documentation.

This is part of the default development docker compose setup and can be reached via `http://127.0.0.1:8000`.

To adjust the documentation files look into the `docs` directory and the configuration of MkDocs is in `mkdocs.yml`. 

##### Setup

Run `make up_docs` to start MkDocs. Set environment variable `HTTP_PORT_DOCS` to adjust host port. 

#### REST-Api

Checkout the API docs via the url `http://127.0.0.1/docs/api`.

This uses the schema defined in the file `/docs/openapi.json`. Please adjust openapi schema if you change the API.

#### Useful links

- [Trakt API docs](https://trakt.docs.apiary.io/)
- [TMDB API docs](https://developers.themoviedb.org/3)
- [OpenAPI API docs](https://swagger.io/docs/specification/about/)
