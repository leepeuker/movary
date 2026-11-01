### Local development setup

First steps for local development setup:

- Clone the repository
- Copy the file `.env.example` to `.env` and customize it for your local environment
    - Set `USER_ID` and `GROUP_ID` to the UID and GID owning the local files
      (`id -u` and `id -g`)
    - Add your `TMDB_API_KEY`
    - Generate `APPLICATION_SECRET` with `openssl rand -hex 32`
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

### Build version metadata

Production builds accept `APPLICATION_VERSION` and `APPLICATION_REVISION` build arguments
and write them to `/app/VERSION` and `/app/REVISION`. Both default to `unknown` when omitted.
Release and nightly workflows supply the version and commit from the same source used for
the OCI image metadata. These files are build artifacts outside persistent storage;
the application version cannot be overridden through runtime configuration.

### HTTP endpoint tests

Run all standard checks and test suites, including the HTTP endpoint tests, with:

```bash
make test
```

This runs the Composer checks and unit tests first, then runs the real-HTTP tests in their disposable application container.

Run the real-HTTP test suite with:

```bash
make http_test
```

This builds and starts one disposable development image containing both the application and the PHPUnit runner. The container uses a temporary SQLite database, publishes no host ports, has no external network access, and is removed after the test run. It does not use or modify the developer's running Movary containers or database.

The tests create fixtures through Movary's domain services and send requests through Nginx and
PHP-FPM. Put versioned client tests in `tests/http/Api/`, webhook tests in
`tests/http/Webhooks/`, and feed tests in `tests/http/Feeds/`. Shared HTTP test harness classes
live directly in `tests/http/`. Keep fast isolated tests under `tests/unit/`.

### Frontend dependencies

Third-party CSS, JavaScript, fonts, source maps, and licenses are managed with npm. npm always runs in the dedicated frontend Docker container, so Node.js and npm do not need to be installed on the host.

Application-owned files remain in `public/css` and `public/js`. Generated third-party files are committed under `public/vendor` and must not be edited manually. The production image uses these committed files and does not install Node.js or download npm packages.

Twig templates reference these directories with `css_url()`, `js_url()`, and `vendor_url()`. All three helpers add the same content-based cache version. Files stored directly in `public`, such as `serviceWorker.js`, use `applicationUrl` without an asset helper.

Use the following commands:

- `make frontend_install` installs the exact versions from `package-lock.json`
- `make frontend_sync` installs the locked dependencies and regenerates `public/vendor`
- `make frontend_check` installs the locked dependencies and verifies that `public/vendor` is current without modifying it

CI runs `make frontend_check` from a clean checkout and audits the locked production dependencies for known security advisories. Changes to `package.json`, `package-lock.json`, the asset copy manifest, and `public/vendor` must therefore remain synchronized.

To update a dependency, install an explicit version through the frontend container. For example:

```bash
docker compose -f docker-compose.frontend.yml run --rm frontend npm install --ignore-scripts --save-exact bootstrap@5.3.1
make frontend_sync
make frontend_check
```

Commit `package.json`, `package-lock.json`, and the regenerated `public/vendor` files together. Dependency updates should remain separate from unrelated application changes.

### Xdebug

You can change `XDEBUG_MODE` to `debug` in the `.env` file to enable debugging using [Xdebug](https://xdebug.org/) in the development docker container.

For example, in VSCode or VSCodium, the following debug configuration would allow you to set breakpoints and debug the PHP code:

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/app": "${workspaceRoot}"
      }
    }
  ]
}
```

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

Validate the OpenAPI schema locally with `make openapi_validate`. This requires Docker.

#### Useful links

- [Trakt API docs](https://trakt.docs.apiary.io/)
- [TMDB API docs](https://developers.themoviedb.org/3)
- [OpenAPI API docs](https://swagger.io/docs/specification/about/)
