#!/usr/bin/env bash

set -euo pipefail

image_reference=${1:-movary-api-test}
composer_script=${2:-test-api}
timeout_seconds=${API_TEST_TIMEOUT_SECONDS:-120}
database_directory=$(mktemp -d)
container_name="movary-api-test-$$-$RANDOM"
application_secret='0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'

cleanup() {
    local exit_status=$?

    if [[ $exit_status -ne 0 ]]; then
        docker logs "$container_name" >&2 2>/dev/null || true
    fi

    docker rm --force "$container_name" >/dev/null 2>&1 || true
    rm -rf "$database_directory"
}
trap cleanup EXIT

if [[ ! $timeout_seconds =~ ^[1-9][0-9]*$ ]]; then
    echo "API_TEST_TIMEOUT_SECONDS must be a positive integer: $timeout_seconds" >&2
    exit 1
fi

chmod 0777 "$database_directory"

docker run --detach \
    --name "$container_name" \
    --network none \
    --env "APPLICATION_SECRET=$application_secret" \
    --env DATABASE_MODE=sqlite \
    --env DATABASE_SQLITE=../api-test-data/movary.sqlite \
    --env TMDB_API_KEY=not-used \
    --volume "$database_directory:/api-test-data" \
    "$image_reference" >/dev/null

deadline=$((SECONDS + timeout_seconds))
application_ready=false

while ((SECONDS < deadline)); do
    container_status=$(docker inspect --format '{{.State.Status}}' "$container_name")
    if [[ $container_status != running ]]; then
        echo "API test container stopped before the application was ready (status: $container_status)" >&2
        exit 1
    fi

    if [[ $(docker exec "$container_name" curl --silent http://127.0.0.1:8080/fpm-ping 2>/dev/null || true) == pong ]]; then
        application_ready=true
        break
    fi

    sleep 1
done

if [[ $application_ready != true ]]; then
    echo "Application did not become ready within $timeout_seconds seconds" >&2
    exit 1
fi

docker exec \
    --env API_BASE_URL=http://127.0.0.1:8080 \
    "$container_name" \
    composer "$composer_script"
