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

To apply the code style rules use the following features:
- `Reformat code` (more info [here](https://www.jetbrains.com/help/phpstorm/rearrange-code.html))
- `Rearrange code` (more info [here](https://www.jetbrains.com/help/phpstorm/rearrange-code.html))
- `Optimize imports`

Notes:
- Please apply the code style rules for every file you changed
- You can search for these features in the Settings under Keymap to find their default shortcuts and customize them
- If you use the PhpStorm UI for git you can execute the features listed above automatically before every commit (Settings -> Version Control -> Commit -> Commit Checks)


### Api docs

Checkout the API docs via the url path `{your-movie-url}/doecho $UIDcs/api`.

This uses the schema defined in the file `/docs/openapi.json`

### Useful links

- [Trakt API docs](https://trakt.docs.apiary.io/)
- [TMDB API docs](https://developers.themoviedb.org/3)
