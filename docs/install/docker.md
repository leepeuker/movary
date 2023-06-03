This is the recommended way to run the app.

You must provide a tmdb api key (get one [here](https://www.themoviedb.org/settings/api)).

Example using SQLite:

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

Example using MySQL:

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

Example docker-compose.yml with a MySQL server

```yml
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

More configuration can be done via the base image webdevops/php-nginx, checkout
their [docs](https://dockerfile.readthedocs.io/en/latest/content/DockerImages/dockerfiles/php-nginx.html) for more.

Continue with [First steps](../first-steps.md)...
