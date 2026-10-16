# Docker installation

The [official Docker image](https://hub.docker.com/r/leepeuker/movary) is the recommended way to run Movary. It includes nginx, PHP-FPM, the background job worker, and automatic database migrations.

## Before you start

Install Docker Engine and the Docker Compose plugin, then choose a database:

- **SQLite** is the simplest option and is recommended for most installations.
- **MySQL** is available when you prefer a separate database server.

Prepare these settings before starting Movary:

| Setting | Requirement |
|:--------|:------------|
| `APPLICATION_SECRET` | Required. Generate it with `openssl rand -hex 32`, then keep it stable across restarts and identical across all Movary instances. |
| `TMDB_API_KEY` | Required for movie metadata. Get a key from [TMDB](https://www.themoviedb.org/settings/api). It can also be configured in the Web UI after startup. |
| `APPLICATION_URL` | Recommended. Set it to the public URL, such as `https://movary.example.com`. It is required for email verification and password resets. |
| `TIMEZONE` | Optional. Defaults to `UTC`; use a [supported PHP timezone](https://www.php.net/manual/en/timezones.php). |

MySQL additionally requires `DATABASE_MYSQL_HOST`, `DATABASE_MYSQL_NAME`, `DATABASE_MYSQL_USER`, and `DATABASE_MYSQL_PASSWORD`. See [Configuration](../configuration.md) for every available setting.

!!! warning

    Do not change `APPLICATION_SECRET` after setup. Movary uses it to derive cryptographic keys.

## Database migrations

The Docker image automatically runs missing database migrations when the container starts. Back up your database and storage before updating Movary.

## Image tags

- `latest`: latest stable version and recommended for most users
- `nightly`: latest development changes; unstable, use with caution
- `X.Y.Z`: a specific released version

## Persistent storage

Movary stores generated files, including logs and cached images, in `/app/storage`. Persist this directory outside the container.

The examples below use a managed Docker volume. If you use a bind mount instead, create the host directory before starting the container and ensure user and group `3000:3000` can read and write it.

## Examples

Replace every value enclosed in angle brackets before starting the container.

### SQLite

```shell
docker volume create movary-storage
docker run -d \
  --name movary \
  --restart unless-stopped \
  -p 80:8080 \
  -e APPLICATION_SECRET="<application_secret>" \
  -e TMDB_API_KEY="<tmdb_key>" \
  -e APPLICATION_URL="https://movary.example.com" \
  -e TIMEZONE="Europe/Berlin" \
  -e DATABASE_MODE="sqlite" \
  -v movary-storage:/app/storage \
  leepeuker/movary:latest
```

### Existing MySQL server

```shell
docker volume create movary-storage
docker run -d \
  --name movary \
  --restart unless-stopped \
  -p 80:8080 \
  -e APPLICATION_SECRET="<application_secret>" \
  -e TMDB_API_KEY="<tmdb_key>" \
  -e APPLICATION_URL="https://movary.example.com" \
  -e TIMEZONE="Europe/Berlin" \
  -e DATABASE_MODE="mysql" \
  -e DATABASE_MYSQL_HOST="<host>" \
  -e DATABASE_MYSQL_NAME="<database_name>" \
  -e DATABASE_MYSQL_USER="<database_user>" \
  -e DATABASE_MYSQL_PASSWORD="<database_password>" \
  -v movary-storage:/app/storage \
  leepeuker/movary:latest
```

### Docker Compose with MySQL

```yaml
services:
  movary:
    image: leepeuker/movary:latest
    container_name: movary
    restart: unless-stopped
    ports:
      - "80:8080"
    environment:
      APPLICATION_SECRET: "<application_secret>"
      TMDB_API_KEY: "<tmdb_key>"
      APPLICATION_URL: "https://movary.example.com"
      TIMEZONE: "Europe/Berlin"
      DATABASE_MODE: "mysql"
      DATABASE_MYSQL_HOST: "mysql"
      DATABASE_MYSQL_NAME: "movary"
      DATABASE_MYSQL_USER: "movary_user"
      DATABASE_MYSQL_PASSWORD: "<database_password>"
    volumes:
      - movary-storage:/app/storage

  mysql:
    image: mysql:8.0
    container_name: movary-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: "movary"
      MYSQL_USER: "movary_user"
      MYSQL_PASSWORD: "<database_password>"
      MYSQL_ROOT_PASSWORD: "<mysql_root_password>"
    volumes:
      - movary-db:/var/lib/mysql

volumes:
  movary-db:
  movary-storage:
```

Start the services from the directory containing `docker-compose.yml`:

```shell
docker compose up -d
```

## Docker secrets

Every environment variable can be read from a file by appending `_FILE` to its name. The direct environment variable takes precedence, so do not set both forms.

This Compose example keeps sensitive values in Docker secrets:

```yaml
services:
  movary:
    image: leepeuker/movary:latest
    container_name: movary
    restart: unless-stopped
    ports:
      - "80:8080"
    environment:
      APPLICATION_SECRET_FILE: /run/secrets/application_secret
      TMDB_API_KEY_FILE: /run/secrets/tmdb_key
      APPLICATION_URL: "https://movary.example.com"
      TIMEZONE: "Europe/Berlin"
      DATABASE_MODE: "mysql"
      DATABASE_MYSQL_HOST: "mysql"
      DATABASE_MYSQL_NAME: "movary"
      DATABASE_MYSQL_USER: "movary_user"
      DATABASE_MYSQL_PASSWORD_FILE: /run/secrets/mysql_password
    volumes:
      - movary-storage:/app/storage
    secrets:
      - application_secret
      - tmdb_key
      - mysql_password

  mysql:
    image: mysql:8.0
    container_name: movary-db
    restart: unless-stopped
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
  application_secret:
    file: /path/to/docker/secret/application_secret
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

For more information, see the [Docker secrets documentation](https://docs.docker.com/engine/swarm/secrets/).

## Verify the installation

Check that the container is running and review its startup logs:

```shell
docker ps --filter name=movary
docker logs movary
```

Open `APPLICATION_URL` in a browser and continue with [Getting started](../getting-started.md).
