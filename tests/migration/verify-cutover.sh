#!/usr/bin/env bash

set -euo pipefail

image_name="${1:-movary}"
audit_directory=$(mktemp -d)
chmod 0777 "$audit_directory"
network_name="movary-migration-test-$$"
mysql_container="movary-migration-test-mysql-$$"

cleanup() {
    docker rm --force "$mysql_container" >/dev/null 2>&1 || true
    docker network rm "$network_name" >/dev/null 2>&1 || true
    rm -rf "$audit_directory"
}
trap cleanup EXIT

run_sqlite_migrations() {
    local database_file=$1
    shift

    docker run --rm --entrypoint php \
        --env DATABASE_MODE=sqlite \
        --env "DATABASE_SQLITE=/audit/$database_file" \
        --volume "$audit_directory:/audit" \
        "$image_name" \
        vendor/bin/phinx migrate --configuration settings/phinx.php "$@"
}

run_mysql_migrations() {
    local database_name=$1
    shift

    docker run --rm --entrypoint php \
        --network "$network_name" \
        --env DATABASE_MODE=mysql \
        --env DATABASE_MYSQL_HOST="$mysql_container" \
        --env DATABASE_MYSQL_NAME="$database_name" \
        --env DATABASE_MYSQL_USER=movary \
        --env DATABASE_MYSQL_PASSWORD=movary \
        --env DATABASE_MYSQL_PORT=3306 \
        "$image_name" \
        vendor/bin/phinx migrate --configuration settings/phinx.php "$@"
}

query_sqlite() {
    local database_file=$1
    local query=$2

    docker run --rm --entrypoint php \
        --volume "$audit_directory:/audit" \
        "$image_name" \
        -r '$database = new SQLite3("/audit/" . $argv[1]); $result = $database->querySingle($argv[2]); if ($result === false) { exit(1); } echo $result;' \
        "$database_file" "$query"
}

assert_equal() {
    local expected=$1
    local actual=$2
    local message=$3

    if [[ "$actual" != "$expected" ]]; then
        echo "$message: expected '$expected', got '$actual'" >&2
        exit 1
    fi
}

run_sqlite_migrations before.sqlite --target 20260927143000
cp "$audit_directory/before.sqlite" "$audit_directory/null-genre.sqlite"
cp "$audit_directory/before.sqlite" "$audit_directory/noncanonical.sqlite"
chmod 0666 "$audit_directory/null-genre.sqlite" "$audit_directory/noncanonical.sqlite"

query_sqlite null-genre.sqlite \
    "INSERT INTO genre (id, name, tmdb_id, created_at) VALUES (1, 'a', NULL, '2026-01-01'), (2, 'b', NULL, '2026-01-01')"
run_sqlite_migrations null-genre.sqlite
assert_equal 2 "$(query_sqlite null-genre.sqlite 'SELECT COUNT(*) FROM genre WHERE tmdb_id IS NULL')" \
    'SQLite nullable genre IDs were not preserved'

query_sqlite noncanonical.sqlite \
    "INSERT INTO user (id, email, name, password, created_at) VALUES (1, 'a@example.test', 'a', 'x', '2026-01-01')"
query_sqlite noncanonical.sqlite \
    "INSERT INTO location (id, user_id, name, created_at) VALUES (1, '01', 'bad', '2026-01-01')"
if run_sqlite_migrations noncanonical.sqlite; then
    echo 'SQLite accepted a non-canonical foreign-key value' >&2
    exit 1
fi
assert_equal 01 "$(query_sqlite noncanonical.sqlite 'SELECT user_id FROM location')" \
    'SQLite changed a rejected foreign-key value'
assert_equal 0 "$(query_sqlite noncanonical.sqlite 'SELECT COUNT(*) FROM phinxlog WHERE version = 20260927220000')" \
    'SQLite recorded a rejected normalization migration'

docker network create "$network_name" >/dev/null
docker run --detach --rm \
    --name "$mysql_container" \
    --network "$network_name" \
    --env MYSQL_ROOT_PASSWORD=movary-root \
    --env MYSQL_DATABASE=movary \
    --env MYSQL_USER=movary \
    --env MYSQL_PASSWORD=movary \
    mysql:8.0 >/dev/null

for attempt in {1..30}; do
    if docker exec "$mysql_container" mysqladmin ping --host=127.0.0.1 --user=movary --password=movary >/dev/null 2>&1; then
        break
    fi
    if [[ $attempt -eq 30 ]]; then
        echo 'MySQL did not become ready' >&2
        exit 1
    fi
    sleep 1
done

run_mysql_migrations movary --target 20260927143000
docker exec "$mysql_container" mysqldump --no-tablespaces --user=movary --password=movary movary >"$audit_directory/mysql-before.sql"
docker exec "$mysql_container" mysql --user=root --password=movary-root \
    --execute="CREATE DATABASE movary_drift; GRANT ALL PRIVILEGES ON movary_drift.* TO 'movary'@'%';"
docker exec --interactive "$mysql_container" mysql --user=movary --password=movary movary_drift \
    <"$audit_directory/mysql-before.sql"

run_mysql_migrations movary
docker exec "$mysql_container" mysql --user=root --password=movary-root movary_drift \
    --execute='ALTER TABLE movie_cast DROP FOREIGN KEY movie_cast_ibfk_1'
if run_mysql_migrations movary_drift; then
    echo 'MySQL accepted an unexpected pre-normalization schema' >&2
    exit 1
fi

mysql_query() {
    docker exec "$mysql_container" mysql --batch --skip-column-names \
        --user=root --password=movary-root movary_drift --execute="$1"
}

assert_equal 0 "$(mysql_query 'SELECT COUNT(*) FROM phinxlog WHERE version = 20260927220000')" \
    'MySQL recorded a rejected normalization migration'
assert_equal uniqueTraktId "$(mysql_query "SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'movary_drift' AND TABLE_NAME = 'cache_trakt_user_movie_watched' AND INDEX_NAME = 'uniqueTraktId'")" \
    'MySQL changed the legacy watched-cache index before rejecting drift'
assert_equal "NO ACTION" "$(mysql_query "SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'movary_drift' AND TABLE_NAME = 'job_queue' AND CONSTRAINT_NAME = 'job_queue_ibfk_1'")" \
    'MySQL changed the job-queue foreign key before rejecting drift'
