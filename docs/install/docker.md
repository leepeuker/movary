## Introduction

It is recommended to host Movary with the [official Docker image](https://hub.docker.com/r/leepeuker/movary).

The official docker image extends the `TrafeX/docker-php-nginx` image, checkout
their [docs](https://github.com/TrafeX/docker-php-nginx) for more configuration information.

!!! warning

    After the **initial installation** and every update containing database changes the database migrations must be executed:

    `php bin/console.php database:migration:migrate`

    Missing database migrations can cause criticatal errors!

!!! info
    
    The docker images automatically runs the missing database migrations on start up. 
    To stop this behavior set the environment variable `DATABASE_DISABLE_AUTO_MIGRATION=1`

## Image tags

- `latest` Default image. Latest stable version and **recommended** for the average user
- `nightly` Has the latest changes as soon as possible. Warning: Not stable, use with caution
- `X.Y.Z` There is a tag for every individual version

## Storage permissions

The `/app/storage` directory is used to store all created files (e.g. logs and images).
It should be persisted outside the container and Movary needs read/write access to it.

The easiest way to do this are managed docker volumes (used in the examples below).

!!! info

    If you bind a local mount, make sure the directory exists before you start the container
    and that it has the necessary permissions/ownership (3000:3000 on default).

## Docker secrets

Docker secrets can be used for all environment variables, just append `_FILE` to the environment variable name.
Secrets are used as a fallback for not existing environment variables.
Make sure to not set the environment variable without the `_FILE` suffix if you want to use a secret.

For more info on Docker secrets, read the [official Docker documentation](https://docs.docker.com/engine/swarm/secrets/).

## Examples

All examples include the environment variable `TMDB_API_KEY` (get a key [here](https://www.themoviedb.org/settings/api)).
It is not strictly required to be set here but recommend.
Many features of the application will not work correctly without it.

### With SQLite

This is the easiest setup and especially recommend for beginners

```shell
$ docker volume create movary-storage
$ docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e TMDB_API_KEY="<tmdb_key>" \
  -e DATABASE_MODE="sqlite" \
  -v movary-storage:/app/storage \
  leepeuker/movary:latest
```

### With MySQL

```shell
$ docker volume create movary-storage
$ docker run --rm -d \
  --name movary \
  -p 80:80 \
  -e TMDB_API_KEY="<tmdb_key>" \
  -e DATABASE_MODE="mysql" \
  -e DATABASE_MYSQL_HOST="<host>" \
  -e DATABASE_MYSQL_NAME="<db_name>" \
  -e DATABASE_MYSQL_USER="<db_user>" \
  -e DATABASE_MYSQL_PASSWORD="<db_password>" \
  -v movary-storage:/app/storage \
  leepeuker/movary:latest
```

### docker-compose.yml with MySQL

```yaml
version: "3.5"

services:
  movary:
    image: leepeuker/movary:latest
    container_name: movary
    ports:
      - "80:80"
    environment:
      TMDB_API_KEY: "<tmdb_key>"
      DATABASE_MODE: "mysql"
      DATABASE_MYSQL_HOST: "mysql"
      DATABASE_MYSQL_NAME: "movary"
      DATABASE_MYSQL_USER: "movary_user"
      DATABASE_MYSQL_PASSWORD: "movary_password"
    volumes:
      - movary-storage:/app/storage

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: "movary"
      MYSQL_USER: "movary_user"
      MYSQL_PASSWORD: "movary_password"
      MYSQL_ROOT_PASSWORD: "<mysql_root_password>"
    volumes:
      - movary-db:/var/lib/mysql

volumes:
  movary-db:
  movary-storage:
```

### docker-compose.yml with MySQL and secrets

```yaml
version: "3.5"

services:
  movary:
    image: leepeuker/movary:latest
    container_name: movary
    ports:
      - "80:80"
    environment:
      TMDB_API_KEY_FILE: /run/secrets/tmdb_key
      DATABASE_MODE: "mysql"
      DATABASE_MYSQL_HOST: "mysql"
      DATABASE_MYSQL_NAME: "movary"
      DATABASE_MYSQL_USER: "movary_user"
      DATABASE_MYSQL_PASSWORD_FILE: /run/secrets/mysql_password
    volumes:
      - movary-storage:/app/storage
    secrets:
      - tmdb_key
      - mysql_password

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: "movary"
      MYSQL_USER: "movary_user"
      MYSQL_PASSWORD_FILE: /run/secrets/mysql_password
      MYSQL_ROOT_PASSWORD_FILE: /run/secrets/mysql_root_password
    volumes:
      - movary-db:/var/lib/mysql
    secrets:
      - mysql_root_password
      - mysql_password

secrets:
  mysql_root_password:
    file: /path/to/docker/secret/mysql_root_password
  mysql_password:
    file: /path/to/docker/secret/mysql_password
  tmdb_key:
    file: /path/to/docker/secret/tmdb_key

volumes:
  movary-db:
  movary-storage:
```
