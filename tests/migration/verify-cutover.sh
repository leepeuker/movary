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
cp "$audit_directory/before.sqlite" "$audit_directory/repairable.sqlite"
cp "$audit_directory/before.sqlite" "$audit_directory/noncanonical.sqlite"
chmod 0666 "$audit_directory/null-genre.sqlite" "$audit_directory/repairable.sqlite" "$audit_directory/noncanonical.sqlite"

query_sqlite null-genre.sqlite \
    "INSERT INTO genre (id, name, tmdb_id, created_at) VALUES (1, 'a', NULL, '2026-01-01'), (2, 'b', NULL, '2026-01-01')"
query_sqlite null-genre.sqlite \
    "INSERT INTO company (id, name, created_at) VALUES (1, 'company', '2026-01-01')"
query_sqlite null-genre.sqlite \
    "INSERT INTO movie (id, title, tmdb_id, created_at) VALUES (1, 'movie', 1, '2026-01-01')"
query_sqlite null-genre.sqlite \
    "INSERT INTO movie_genre (genre_id, movie_id, position) VALUES (1, 1, 1)"
query_sqlite null-genre.sqlite \
    "INSERT INTO movie_production_company (company_id, movie_id, position) VALUES (1, 1, 1)"
run_sqlite_migrations null-genre.sqlite
assert_equal 2 "$(query_sqlite null-genre.sqlite 'SELECT COUNT(*) FROM genre WHERE tmdb_id IS NULL')" \
    'SQLite nullable genre IDs were not preserved'
assert_equal 1 "$(query_sqlite null-genre.sqlite 'SELECT COUNT(*) FROM movie_genre WHERE genre_id = 1 AND movie_id = 1 AND position = 1')" \
    'SQLite movie genre relations were not preserved'
assert_equal 1 "$(query_sqlite null-genre.sqlite 'SELECT COUNT(*) FROM movie_production_company WHERE company_id = 1 AND movie_id = 1 AND position = 1')" \
    'SQLite movie production company relations were not preserved'
assert_equal genre_id,movie_id "$(query_sqlite null-genre.sqlite "SELECT GROUP_CONCAT(name, ',') FROM pragma_index_info('unique_movie_genre')")" \
    'SQLite movie genre key does not match the Doctrine starting schema'
assert_equal company_id,movie_id "$(query_sqlite null-genre.sqlite "SELECT GROUP_CONCAT(name, ',') FROM pragma_index_info('unique_movie_production_company')")" \
    'SQLite movie production company key does not match the Doctrine starting schema'
assert_equal 0 "$(query_sqlite null-genre.sqlite "SELECT COUNT(*) FROM pragma_index_list('movie_genre') WHERE name = 'index_movie_genre_genre_id'")" \
    'SQLite retained a redundant movie genre index'
assert_equal 0 "$(query_sqlite null-genre.sqlite "SELECT COUNT(*) FROM pragma_index_list('movie_production_company') WHERE name = 'index_movie_production_company_company_id'")" \
    'SQLite retained a redundant movie production company index'
assert_equal user_id,trakt_id "$(query_sqlite null-genre.sqlite "SELECT GROUP_CONCAT(name, ',') FROM (SELECT name FROM pragma_table_info('cache_trakt_user_movie_rating') WHERE pk > 0 ORDER BY pk)")" \
    'SQLite rating cache does not have the expected composite primary key'
assert_equal user_id,trakt_id "$(query_sqlite null-genre.sqlite "SELECT GROUP_CONCAT(name, ',') FROM (SELECT name FROM pragma_table_info('cache_trakt_user_movie_watched') WHERE pk > 0 ORDER BY pk)")" \
    'SQLite watched cache does not have the expected composite primary key'
query_sqlite null-genre.sqlite \
    "INSERT INTO user (id, email, name, password, created_at) VALUES (1, 'first@example.test', 'first', 'x', '2026-01-01'), (2, 'second@example.test', 'second', 'x', '2026-01-01')"
query_sqlite null-genre.sqlite \
    "INSERT INTO cache_trakt_user_movie_rating (trakt_id, user_id, rating, rated_at) VALUES (123, 1, 8, '2026-01-01'), (123, 2, 9, '2026-01-01')"
query_sqlite null-genre.sqlite \
    "INSERT INTO cache_trakt_user_movie_watched (trakt_id, user_id, last_updated_at) VALUES (123, 1, '2026-01-01'), (123, 2, '2026-01-01')"
assert_equal 2 "$(query_sqlite null-genre.sqlite 'SELECT COUNT(*) FROM cache_trakt_user_movie_rating WHERE trakt_id = 123')" \
    'SQLite rating cache does not isolate identical Trakt IDs by user'
assert_equal 2 "$(query_sqlite null-genre.sqlite 'SELECT COUNT(*) FROM cache_trakt_user_movie_watched WHERE trakt_id = 123')" \
    'SQLite watched cache does not isolate identical Trakt IDs by user'

query_sqlite repairable.sqlite \
    "INSERT INTO user (id, email, name, password, created_at) VALUES (1, 'a@example.test', 'a', 'x', '2026-01-01')"
query_sqlite repairable.sqlite \
    "INSERT INTO location (id, user_id, name, created_at) VALUES (1, '01', 'leading zero', '2026-01-01')"
query_sqlite repairable.sqlite \
    "INSERT INTO cache_tmdb_languages (iso_639_1, english_name) VALUES (NULL, 'invalid')"
query_sqlite repairable.sqlite \
    "INSERT INTO cache_trakt_user_movie_rating (trakt_id, user_id, rated_at) VALUES (10, NULL, '2026-01-01')"
query_sqlite repairable.sqlite \
    "INSERT INTO cache_trakt_user_movie_watched (trakt_id, user_id, last_updated_at) VALUES (10, NULL, '2026-01-01')"
query_sqlite repairable.sqlite \
    "INSERT INTO person (id, name, gender, tmdb_id, created_at) VALUES (1, 'invalid gender', 9, 1, '2026-01-01')"
run_sqlite_migrations repairable.sqlite
assert_equal integer:1 "$(query_sqlite repairable.sqlite "SELECT TYPEOF(user_id) || ':' || user_id FROM location WHERE id = 1")" \
    'SQLite did not normalize a digit-only user ID'
assert_equal 0 "$(query_sqlite repairable.sqlite 'SELECT COUNT(*) FROM cache_tmdb_languages')" \
    'SQLite did not remove an unusable TMDB language cache row'
assert_equal 0 "$(query_sqlite repairable.sqlite 'SELECT COUNT(*) FROM cache_trakt_user_movie_rating')" \
    'SQLite did not remove an unusable Trakt rating cache row'
assert_equal 0 "$(query_sqlite repairable.sqlite 'SELECT COUNT(*) FROM cache_trakt_user_movie_watched')" \
    'SQLite did not remove an unusable Trakt watched cache row'
assert_equal 0 "$(query_sqlite repairable.sqlite 'SELECT gender FROM person WHERE id = 1')" \
    'SQLite did not normalize unsupported person gender metadata'

query_sqlite noncanonical.sqlite \
    "INSERT INTO user (id, email, name, password, created_at) VALUES (1, 'a@example.test', 'a', 'x', '2026-01-01')"
query_sqlite noncanonical.sqlite \
    "INSERT INTO location (id, user_id, name, created_at) VALUES (1, '1x', 'bad', '2026-01-01')"
if run_sqlite_migrations noncanonical.sqlite; then
    echo 'SQLite accepted a non-canonical foreign-key value' >&2
    exit 1
fi
assert_equal 1x "$(query_sqlite noncanonical.sqlite 'SELECT user_id FROM location')" \
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
mysql_query() {
    local database_name=$1
    local query=$2

    docker exec "$mysql_container" mysql --batch --skip-column-names \
        --user=root --password=movary-root "$database_name" --execute="$query"
}

mysql_query movary "SET SESSION sql_mode = ''; INSERT INTO person (name, gender, tmdb_id, created_at) VALUES ('gender 0', '0', 990, '2026-01-01'), ('gender 1', '1', 991, '2026-01-01'), ('gender 2', '2', 992, '2026-01-01'), ('gender 3', '3', 993, '2026-01-01'), ('invalid gender', 'invalid', 999, '2026-01-01')"
docker exec "$mysql_container" mysqldump --no-tablespaces --user=movary --password=movary movary >"$audit_directory/mysql-before.sql"
docker exec "$mysql_container" mysql --user=root --password=movary-root \
    --execute="CREATE DATABASE movary_drift; GRANT ALL PRIVILEGES ON movary_drift.* TO 'movary'@'%';"
docker exec --interactive "$mysql_container" mysql --user=movary --password=movary movary_drift \
    <"$audit_directory/mysql-before.sql"

run_mysql_migrations movary
assert_equal 0,1,2,3 "$(mysql_query movary 'SELECT GROUP_CONCAT(gender ORDER BY tmdb_id) FROM person WHERE tmdb_id BETWEEN 990 AND 993')" \
    'MySQL changed valid person gender values while removing the enum'
assert_equal 0 "$(mysql_query movary "SELECT gender FROM person WHERE tmdb_id = 999")" \
    'MySQL did not normalize unsupported person gender metadata'
assert_equal user_id,trakt_id "$(mysql_query movary "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'movary' AND TABLE_NAME = 'cache_trakt_user_movie_rating' AND INDEX_NAME = 'PRIMARY'")" \
    'MySQL rating cache does not have the expected composite primary key'
assert_equal user_id,trakt_id "$(mysql_query movary "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'movary' AND TABLE_NAME = 'cache_trakt_user_movie_watched' AND INDEX_NAME = 'PRIMARY'")" \
    'MySQL watched cache does not have the expected composite primary key'
mysql_query movary "INSERT INTO user (id, email, name, password, created_at) VALUES (101, 'first@example.test', 'first', 'x', '2026-01-01'), (102, 'second@example.test', 'second', 'x', '2026-01-01')"
mysql_query movary "INSERT INTO cache_trakt_user_movie_rating (trakt_id, user_id, rating, rated_at) VALUES (123, 101, 8, '2026-01-01'), (123, 102, 9, '2026-01-01')"
mysql_query movary "INSERT INTO cache_trakt_user_movie_watched (trakt_id, user_id, last_updated_at) VALUES (123, 101, '2026-01-01'), (123, 102, '2026-01-01')"
assert_equal 2 "$(mysql_query movary 'SELECT COUNT(*) FROM cache_trakt_user_movie_rating WHERE trakt_id = 123')" \
    'MySQL rating cache does not isolate identical Trakt IDs by user'
assert_equal 2 "$(mysql_query movary 'SELECT COUNT(*) FROM cache_trakt_user_movie_watched WHERE trakt_id = 123')" \
    'MySQL watched cache does not isolate identical Trakt IDs by user'

docker exec "$mysql_container" mysql --user=root --password=movary-root movary_drift \
    --execute='ALTER TABLE movie_cast DROP FOREIGN KEY movie_cast_ibfk_1'
if run_mysql_migrations movary_drift; then
    echo 'MySQL accepted an unexpected pre-normalization schema' >&2
    exit 1
fi

assert_equal 0 "$(mysql_query movary_drift 'SELECT COUNT(*) FROM phinxlog WHERE version = 20260927220000')" \
    'MySQL recorded a rejected normalization migration'
assert_equal uniqueTraktId "$(mysql_query movary_drift "SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'movary_drift' AND TABLE_NAME = 'cache_trakt_user_movie_watched' AND INDEX_NAME = 'uniqueTraktId'")" \
    'MySQL changed the legacy watched-cache index before rejecting drift'
assert_equal "NO ACTION" "$(mysql_query movary_drift "SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = 'movary_drift' AND TABLE_NAME = 'job_queue' AND CONSTRAINT_NAME = 'job_queue_ibfk_1'")" \
    'MySQL changed the job-queue foreign key before rejecting drift'
