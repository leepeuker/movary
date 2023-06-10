## Introduction

It is recommended to host Movary with the [official Docker image](https://hub.docker.com/r/leepeuker/movary).

The official docker image extends the `webdevops/php-nginx` image, checkout
their [docs](https://dockerfile.readthedocs.io/en/latest/content/DockerImages/dockerfiles/php-nginx.html) for more configuration information.

!!! warning

    After the **initial installation** and after **each image update** execute the database migrations, example:

    `docker exec movary php bin/console.php database:migration:migrate`

    Missing database migrations can cause potentialy criticatal errors!

!!! Info

    All docker examples include the environment variable `TMDB_API_KEY` (get a key [here](https://www.themoviedb.org/settings/api)).
    It is not strictly required to be set here but recommend. 
    Many features of the application will not work correctly without it.

## Image tags

- `latest` The latest released stable version (**recommended**)
- `nightly` The latest changes independent of versioning (caution: experimental)

## Storage permissions

The `storage` directory is used by Movary to store all its files (e.g. logs or images), which means Movary needs read/write access to it.

The easiest way to do this are managed docker volumes (used in the examples below).


!!! warning

    If you bind a local mount, make sure the local directory exists before you start the container
    and that it has the necessary permissions/ownership set.

## Example using SQLite

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

## Example using MySQL

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

## Example docker compose with MySQL

Here is a `docker-compose.yml` template

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
