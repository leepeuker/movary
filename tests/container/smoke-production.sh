#!/usr/bin/env bash

set -euo pipefail

image_reference=${1:?Usage: smoke-production.sh <image> [platform]}
platform=${2:-linux/amd64}
timeout_seconds=${SMOKE_TIMEOUT_SECONDS:-180}
application_secret='0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'
database_directory=$(mktemp -d)
container_name="movary-production-smoke-$$-$RANDOM"

cleanup() {
    local exit_code=$?

    if [[ $exit_code -ne 0 ]]; then
        docker logs "$container_name" 2>/dev/null || true
    fi

    docker rm --force "$container_name" >/dev/null 2>&1 || true
    rm -rf "$database_directory"
}
trap cleanup EXIT

chmod 0777 "$database_directory"

if [[ $image_reference == *@sha256:* ]]; then
    docker pull --platform "$platform" "$image_reference"
elif ! docker image inspect "$image_reference" >/dev/null 2>&1; then
    docker pull --platform "$platform" "$image_reference"
fi
docker run --detach --name "$container_name" --platform "$platform" \
    --env "APPLICATION_SECRET=$application_secret" \
    --env DATABASE_MODE=sqlite \
    --env DATABASE_SQLITE=../smoke/movary.sqlite \
    --volume "$database_directory:/smoke" \
    "$image_reference" >/dev/null

deadline=$((SECONDS + timeout_seconds))
health_status=starting

while ((SECONDS < deadline)); do
    container_status=$(docker inspect --format '{{.State.Status}}' "$container_name")
    health_status=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}missing{{end}}' "$container_name")

    if [[ $container_status != running ]]; then
        echo "Container stopped before becoming healthy (status: $container_status)" >&2
        exit 1
    fi

    if [[ $health_status == healthy ]]; then
        break
    fi

    if [[ $health_status == unhealthy || $health_status == missing ]]; then
        echo "Container health check failed (status: $health_status)" >&2
        exit 1
    fi

    sleep 2
done

if [[ $health_status != healthy ]]; then
    echo "Container did not become healthy within $timeout_seconds seconds" >&2
    exit 1
fi

if [[ $(docker exec "$container_name" curl --silent --fail http://127.0.0.1:8080/fpm-ping) != pong ]]; then
    echo 'The PHP-FPM ping endpoint did not return pong' >&2
    exit 1
fi

if ! docker logs "$container_name" 2>&1 | grep --quiet 'SUCCESS: Automatic database migration succeeded'; then
    echo 'The entrypoint did not report a successful automatic database migration' >&2
    exit 1
fi

docker exec "$container_name" php bin/console.php database:migration:status --no-interaction >/dev/null

processes=$(docker top "$container_name")
for expected_process in supervisord 'php-fpm: master process' 'nginx: master process' 'jobs:process'; do
    if ! grep --fixed-strings --quiet "$expected_process" <<<"$processes"; then
        echo "Expected process is not running: $expected_process" >&2
        exit 1
    fi
done

echo "Production image smoke test passed for $platform: $image_reference"
