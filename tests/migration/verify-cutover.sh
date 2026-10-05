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

run_sqlite_app_command() {
    local database_file=$1
    shift

    docker run --rm --entrypoint php \
        --env DATABASE_MODE=sqlite \
        --env "DATABASE_SQLITE=../audit/$database_file" \
        --volume "$audit_directory:/audit" \
        "$image_name" \
        bin/console.php "$@" --no-interaction
}

run_mysql_app_command() {
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
        bin/console.php "$@" --no-interaction
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

query_sqlite_with_foreign_keys() {
    local database_file=$1
    local query=$2

    docker run --rm --entrypoint php \
        --volume "$audit_directory:/audit" \
        "$image_name" \
        -r '$database = new SQLite3("/audit/" . $argv[1]); $database->exec("PRAGMA foreign_keys = ON"); $result = $database->querySingle($argv[2]); if ($result === false) { exit(1); } echo $result;' \
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
cp "$audit_directory/before.sqlite" "$audit_directory/release-0.73.1.sqlite"
cp "$audit_directory/before.sqlite" "$audit_directory/null-genre.sqlite"
cp "$audit_directory/before.sqlite" "$audit_directory/repairable.sqlite"
cp "$audit_directory/before.sqlite" "$audit_directory/noncanonical.sqlite"
chmod 0666 "$audit_directory/release-0.73.1.sqlite" "$audit_directory/null-genre.sqlite" \
    "$audit_directory/repairable.sqlite" "$audit_directory/noncanonical.sqlite"

query_sqlite release-0.73.1.sqlite \
    "INSERT INTO user (id, email, name, password, jellyfin_access_token, created_at) VALUES (1, 'release@example.test', 'release-user', 'x', 'jellyfin-token', '2026-01-01')"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO movie (id, title, tmdb_id, created_at) VALUES (1, 'release-movie', 1001, '2026-01-01')"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO location (id, user_id, name, created_at) VALUES (1, 1, 'release-location', '2026-01-01')"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO movie_user_rating (movie_id, user_id, rating, created_at) VALUES (1, 1, 8, '2026-01-01')"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO movie_user_watch_dates (movie_id, user_id, watched_at, comment, location_id) VALUES (1, 1, '2026-01-01', 'release-watch', 1)"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO watchlist (movie_id, user_id, added_at) VALUES (1, 1, '2026-01-01')"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO user_api_token (user_id, token, created_at) VALUES (1, '12345678-1234-1234-1234-123456789012', '2026-01-01')"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO user_auth_token (id, user_id, token, device_name, user_agent, expiration_date, created_at) VALUES (1, 1, '12345678901234567890123456789012', 'release-device', 'release-agent', '2027-01-01', '2026-01-01')"
query_sqlite release-0.73.1.sqlite \
    "INSERT INTO server_setting (key, value) VALUES ('release-setting', 'preserved')"

run_sqlite_app_command release-0.73.1.sqlite database:migration:migrate
assert_equal 'Movary\DatabaseMigration\Version20261005130000' \
    "$(query_sqlite release-0.73.1.sqlite 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'SQLite 0.73.1 fixture did not reach the latest Doctrine migration'
assert_equal 'release-user|jellyfin-token' "$(query_sqlite release-0.73.1.sqlite \
    "SELECT name || '|' || jellyfin_access_token FROM user WHERE id = 1")" \
    'SQLite 0.73.1 fixture did not preserve the user and integration data'
assert_equal '8|release-watch|release-location' "$(query_sqlite release-0.73.1.sqlite \
    "SELECT r.rating || '|' || w.comment || '|' || l.name FROM movie_user_rating r INNER JOIN movie_user_watch_dates w ON w.movie_id = r.movie_id AND w.user_id = r.user_id INNER JOIN location l ON l.id = w.location_id WHERE r.movie_id = 1 AND r.user_id = 1")" \
    'SQLite 0.73.1 fixture did not preserve rating and watch data'
assert_equal 1 "$(query_sqlite release-0.73.1.sqlite \
    "SELECT COUNT(*) FROM user_api_token a INNER JOIN user_auth_token u ON u.user_id = a.user_id INNER JOIN watchlist w ON w.user_id = a.user_id WHERE a.user_id = 1")" \
    'SQLite 0.73.1 fixture did not preserve tokens and watchlist data'
assert_equal preserved "$(query_sqlite release-0.73.1.sqlite \
    "SELECT value FROM server_setting WHERE key = 'release-setting'")" \
    'SQLite 0.73.1 fixture did not preserve server settings'
query_sqlite_with_foreign_keys release-0.73.1.sqlite 'DELETE FROM location WHERE id = 1'
assert_equal 1 "$(query_sqlite release-0.73.1.sqlite \
    "SELECT COUNT(*) FROM movie_user_watch_dates WHERE movie_id = 1 AND user_id = 1 AND comment = 'release-watch'")" \
    'SQLite deleted watch history together with its location'
assert_equal 1 "$(query_sqlite release-0.73.1.sqlite \
    'SELECT location_id IS NULL FROM movie_user_watch_dates WHERE movie_id = 1 AND user_id = 1')" \
    'SQLite did not clear the deleted location from watch history'

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

cp "$audit_directory/null-genre.sqlite" "$audit_directory/missing-history.sqlite"
chmod 0666 "$audit_directory/missing-history.sqlite"
query_sqlite missing-history.sqlite "DELETE FROM phinxlog WHERE version = 20260927143000"
if run_sqlite_app_command missing-history.sqlite database:migration:migrate; then
    echo 'SQLite accepted a legacy history with a missing migration' >&2
    exit 1
fi
assert_equal 0 "$(query_sqlite missing-history.sqlite "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'doctrine_migration_versions'")" \
    'SQLite initialized Doctrine metadata for an incomplete legacy history'

run_sqlite_app_command null-genre.sqlite database:migration:migrate
assert_equal 'Movary\DatabaseMigration\Version20261005130000' \
    "$(query_sqlite null-genre.sqlite 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'SQLite legacy database did not record the latest Doctrine migration'
run_sqlite_app_command null-genre.sqlite database:migration:migrate
run_sqlite_app_command null-genre.sqlite database:migration:status >/dev/null
run_sqlite_app_command null-genre.sqlite database:migration:rollback >/dev/null
assert_equal 'Movary\DatabaseMigration\Version20261004100000' \
    "$(query_sqlite null-genre.sqlite 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'SQLite did not roll back the country-reference migration'
assert_equal 251 "$(query_sqlite null-genre.sqlite 'SELECT COUNT(*) FROM country')" \
    'SQLite removed country reference data during rollback'
run_sqlite_app_command null-genre.sqlite database:migration:rollback >/dev/null
assert_equal 'Movary\DatabaseMigration\Version20261003190000' \
    "$(query_sqlite null-genre.sqlite 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'SQLite did not roll back the login-attempt migration'
run_sqlite_app_command null-genre.sqlite database:migration:rollback >/dev/null
assert_equal 'Movary\DatabaseMigration\Version20261003000000' \
    "$(query_sqlite null-genre.sqlite 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'SQLite did not roll back the location foreign-key migration'
if run_sqlite_app_command null-genre.sqlite database:migration:rollback >/dev/null 2>&1; then
    echo 'SQLite rolled back the irreversible authentication-token migration' >&2
    exit 1
fi
assert_equal 'Movary\DatabaseMigration\Version20261003000000' \
    "$(query_sqlite null-genre.sqlite 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'SQLite removed the authentication-token migration after rejected rollback'
run_sqlite_app_command null-genre.sqlite database:migration:migrate

run_sqlite_app_command doctrine-fresh.sqlite database:migration:migrate
assert_equal 28 "$(query_sqlite doctrine-fresh.sqlite \
    "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")" \
    'Fresh SQLite Doctrine database has an unexpected table count'
assert_equal 'Movary\DatabaseMigration\Version20261005130000' \
    "$(query_sqlite doctrine-fresh.sqlite 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'Fresh SQLite database did not execute the latest Doctrine migration'

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

    docker exec "$mysql_container" mysql --batch --raw --skip-column-names \
        --user=root --password=movary-root "$database_name" --execute="$query"
}

mysql_query movary "SET SESSION sql_mode = ''; INSERT INTO person (name, gender, tmdb_id, created_at) VALUES ('gender 0', '0', 990, '2026-01-01'), ('gender 1', '1', 991, '2026-01-01'), ('gender 2', '2', 992, '2026-01-01'), ('gender 3', '3', 993, '2026-01-01'), ('invalid gender', 'invalid', 999, '2026-01-01')"
docker exec "$mysql_container" mysqldump --no-tablespaces --user=movary --password=movary movary >"$audit_directory/mysql-before.sql"
docker exec "$mysql_container" mysql --user=root --password=movary-root \
    --execute="CREATE DATABASE movary_release; CREATE DATABASE movary_drift; GRANT ALL PRIVILEGES ON movary_release.* TO 'movary'@'%'; GRANT ALL PRIVILEGES ON movary_drift.* TO 'movary'@'%';"
docker exec --interactive "$mysql_container" mysql --user=movary --password=movary movary_release \
    <"$audit_directory/mysql-before.sql"
docker exec --interactive "$mysql_container" mysql --user=movary --password=movary movary_drift \
    <"$audit_directory/mysql-before.sql"

mysql_query movary_release "INSERT INTO user (id, email, name, password, jellyfin_access_token, created_at) VALUES (1, 'release@example.test', 'release-user', 'x', 'jellyfin-token', '2026-01-01')"
mysql_query movary_release "INSERT INTO movie (id, title, tmdb_id, created_at) VALUES (1, 'release-movie', 1001, '2026-01-01')"
mysql_query movary_release "INSERT INTO location (id, user_id, name, created_at) VALUES (1, 1, 'release-location', '2026-01-01')"
mysql_query movary_release "INSERT INTO movie_user_rating (movie_id, user_id, rating, created_at) VALUES (1, 1, 8, '2026-01-01')"
mysql_query movary_release "INSERT INTO movie_user_watch_dates (movie_id, user_id, watched_at, comment, location_id) VALUES (1, 1, '2026-01-01', 'release-watch', 1)"
mysql_query movary_release "INSERT INTO watchlist (movie_id, user_id, added_at) VALUES (1, 1, '2026-01-01')"
mysql_query movary_release "INSERT INTO user_api_token (user_id, token, created_at) VALUES (1, '12345678-1234-1234-1234-123456789012', '2026-01-01')"
mysql_query movary_release "INSERT INTO user_auth_token (id, user_id, token, device_name, user_agent, expiration_date, created_at) VALUES (1, 1, '12345678901234567890123456789012', 'release-device', 'release-agent', '2027-01-01', '2026-01-01')"
mysql_query movary_release "INSERT INTO server_setting (\`key\`, value) VALUES ('release-setting', 'preserved')"

run_mysql_app_command movary_release database:migration:migrate
assert_equal 'Movary\DatabaseMigration\Version20261005130000' \
    "$(mysql_query movary_release 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'MySQL 0.73.1 fixture did not reach the latest Doctrine migration'
assert_equal 'release-user|jellyfin-token' "$(mysql_query movary_release \
    "SELECT CONCAT_WS('|', name, jellyfin_access_token) FROM user WHERE id = 1")" \
    'MySQL 0.73.1 fixture did not preserve the user and integration data'
assert_equal '8|release-watch|release-location' "$(mysql_query movary_release \
    "SELECT CONCAT_WS('|', r.rating, w.comment, l.name) FROM movie_user_rating r INNER JOIN movie_user_watch_dates w ON w.movie_id = r.movie_id AND w.user_id = r.user_id INNER JOIN location l ON l.id = w.location_id WHERE r.movie_id = 1 AND r.user_id = 1")" \
    'MySQL 0.73.1 fixture did not preserve rating and watch data'
assert_equal 1 "$(mysql_query movary_release \
    "SELECT COUNT(*) FROM user_api_token a INNER JOIN user_auth_token u ON u.user_id = a.user_id INNER JOIN watchlist w ON w.user_id = a.user_id WHERE a.user_id = 1")" \
    'MySQL 0.73.1 fixture did not preserve tokens and watchlist data'
assert_equal preserved "$(mysql_query movary_release \
    "SELECT value FROM server_setting WHERE \`key\` = 'release-setting'")" \
    'MySQL 0.73.1 fixture did not preserve server settings'
mysql_query movary_release 'DELETE FROM location WHERE id = 1'
assert_equal 1 "$(mysql_query movary_release \
    "SELECT COUNT(*) FROM movie_user_watch_dates WHERE movie_id = 1 AND user_id = 1 AND comment = 'release-watch'")" \
    'MySQL deleted watch history together with its location'
assert_equal 1 "$(mysql_query movary_release \
    'SELECT location_id IS NULL FROM movie_user_watch_dates WHERE movie_id = 1 AND user_id = 1')" \
    'MySQL did not clear the deleted location from watch history'

run_mysql_migrations movary
assert_equal '2026-01-01 00:00:00' "$(mysql_query movary 'SELECT created_at FROM person WHERE tmdb_id = 990')" \
    'MySQL changed a stored UTC timestamp while converting it to DATETIME'
assert_equal 0,1,2,3 "$(mysql_query movary 'SELECT GROUP_CONCAT(gender ORDER BY tmdb_id) FROM person WHERE tmdb_id BETWEEN 990 AND 993')" \
    'MySQL changed valid person gender values while removing the enum'
assert_equal 0 "$(mysql_query movary "SELECT gender FROM person WHERE tmdb_id = 999")" \
    'MySQL did not normalize unsupported person gender metadata'
assert_equal user_id,trakt_id "$(mysql_query movary "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'movary' AND TABLE_NAME = 'cache_trakt_user_movie_rating' AND INDEX_NAME = 'PRIMARY'")" \
    'MySQL rating cache does not have the expected composite primary key'
docker exec "$mysql_container" mysqldump --no-tablespaces --user=movary --password=movary movary >"$audit_directory/mysql-normalized.sql"
docker exec "$mysql_container" mysql --user=root --password=movary-root \
    --execute="CREATE DATABASE movary_column_drift; CREATE DATABASE movary_constraint_drift; GRANT ALL PRIVILEGES ON movary_column_drift.* TO 'movary'@'%'; GRANT ALL PRIVILEGES ON movary_constraint_drift.* TO 'movary'@'%';"
docker exec --interactive "$mysql_container" mysql --user=movary --password=movary movary_column_drift \
    <"$audit_directory/mysql-normalized.sql"
docker exec --interactive "$mysql_container" mysql --user=movary --password=movary movary_constraint_drift \
    <"$audit_directory/mysql-normalized.sql"
mysql_query movary_column_drift "ALTER TABLE job_queue MODIFY id INT UNSIGNED NOT NULL; ALTER TABLE user_auth_token MODIFY token CHAR(16) NOT NULL"
if column_drift_output=$(run_mysql_app_command movary_column_drift database:migration:migrate 2>&1); then
    echo 'MySQL accepted incompatible column definitions' >&2
    exit 1
fi
grep -q 'Auto-increment mismatch: job_queue.id' <<<"$column_drift_output"
grep -q 'Length mismatch: user_auth_token.token' <<<"$column_drift_output"
mysql_query movary_constraint_drift "ALTER TABLE user DROP CHECK chk_user_mastodon_post_visibility, ADD CONSTRAINT chk_user_mastodon_post_visibility CHECK (mastodon_post_visibility IN ('public'))"
if constraint_drift_output=$(run_mysql_app_command movary_constraint_drift database:migration:migrate 2>&1); then
    echo 'MySQL accepted an incompatible check constraint' >&2
    exit 1
fi
grep -q 'Missing or invalid check constraint: chk_user_mastodon_post_visibility' <<<"$constraint_drift_output"

assert_equal user_id,trakt_id "$(mysql_query movary "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'movary' AND TABLE_NAME = 'cache_trakt_user_movie_watched' AND INDEX_NAME = 'PRIMARY'")" \
    'MySQL watched cache does not have the expected composite primary key'
mysql_query movary "INSERT INTO user (id, email, name, password, created_at) VALUES (101, 'first@example.test', 'first', 'x', '2026-01-01'), (102, 'second@example.test', 'second', 'x', '2026-01-01')"
mysql_query movary "INSERT INTO cache_trakt_user_movie_rating (trakt_id, user_id, rating, rated_at) VALUES (123, 101, 8, '2026-01-01'), (123, 102, 9, '2026-01-01')"
mysql_query movary "INSERT INTO cache_trakt_user_movie_watched (trakt_id, user_id, last_updated_at) VALUES (123, 101, '2026-01-01'), (123, 102, '2026-01-01')"
assert_equal 2 "$(mysql_query movary 'SELECT COUNT(*) FROM cache_trakt_user_movie_rating WHERE trakt_id = 123')" \
    'MySQL rating cache does not isolate identical Trakt IDs by user'
assert_equal 2 "$(mysql_query movary 'SELECT COUNT(*) FROM cache_trakt_user_movie_watched WHERE trakt_id = 123')" \
    'MySQL watched cache does not isolate identical Trakt IDs by user'

run_mysql_app_command movary database:migration:migrate
assert_equal 'Movary\DatabaseMigration\Version20261005130000' \
    "$(mysql_query movary 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'MySQL legacy database did not record the latest Doctrine migration'
run_mysql_app_command movary database:migration:migrate
run_mysql_app_command movary database:migration:status >/dev/null
run_mysql_app_command movary database:migration:rollback >/dev/null
assert_equal 'Movary\DatabaseMigration\Version20261004100000' \
    "$(mysql_query movary 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'MySQL did not roll back the country-reference migration'
assert_equal 251 "$(mysql_query movary 'SELECT COUNT(*) FROM country')" \
    'MySQL removed country reference data during rollback'
run_mysql_app_command movary database:migration:rollback >/dev/null
assert_equal 'Movary\DatabaseMigration\Version20261003190000' \
    "$(mysql_query movary 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'MySQL did not roll back the login-attempt migration'
run_mysql_app_command movary database:migration:rollback >/dev/null
assert_equal 'Movary\DatabaseMigration\Version20261003000000' \
    "$(mysql_query movary 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'MySQL did not roll back the location foreign-key migration'
if run_mysql_app_command movary database:migration:rollback >/dev/null 2>&1; then
    echo 'MySQL rolled back the irreversible authentication-token migration' >&2
    exit 1
fi
assert_equal 'Movary\DatabaseMigration\Version20261003000000' \
    "$(mysql_query movary 'SELECT MAX(version) FROM doctrine_migration_versions')" \
    'MySQL removed the authentication-token migration after rejected rollback'
run_mysql_app_command movary database:migration:migrate

docker exec "$mysql_container" mysql --user=root --password=movary-root \
    --execute="CREATE DATABASE doctrine_fresh; GRANT ALL PRIVILEGES ON doctrine_fresh.* TO 'movary'@'%';"
run_mysql_app_command doctrine_fresh database:migration:migrate
assert_equal 28 "$(mysql_query doctrine_fresh \
    "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'doctrine_fresh'")" \
    'Fresh MySQL Doctrine database has an unexpected table count'
assert_equal 2 "$(mysql_query doctrine_fresh \
    "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = 'doctrine_fresh' AND CONSTRAINT_TYPE = 'CHECK'")" \
    'Fresh MySQL Doctrine database does not have both value-domain checks'

table_signature_query="SELECT CONCAT_WS('|', TABLE_NAME, ENGINE, TABLE_COLLATION)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME NOT IN ('phinxlog', 'doctrine_migration_versions')
    ORDER BY TABLE_NAME"
assert_equal "$(mysql_query movary "$table_signature_query")" \
    "$(mysql_query doctrine_fresh "$table_signature_query")" \
    'Fresh and converted MySQL table options differ'

column_signature_query="SELECT CONCAT_WS(
        '|',
        TABLE_NAME,
        COLUMN_NAME,
        COLUMN_TYPE,
        IS_NULLABLE,
        COALESCE(COLUMN_DEFAULT, '<NULL>'),
        EXTRA,
        COALESCE(CHARACTER_SET_NAME, ''),
        COALESCE(COLLATION_NAME, '')
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME NOT IN ('phinxlog', 'doctrine_migration_versions')
    ORDER BY TABLE_NAME, COLUMN_NAME"
assert_equal "$(mysql_query movary "$column_signature_query")" \
    "$(mysql_query doctrine_fresh "$column_signature_query")" \
    'Fresh and converted MySQL column definitions differ'

index_signature_query="SELECT CONCAT_WS(
        '|',
        TABLE_NAME,
        INDEX_NAME,
        NON_UNIQUE,
        GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME NOT IN ('phinxlog', 'doctrine_migration_versions')
    GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE
    ORDER BY TABLE_NAME, INDEX_NAME"
assert_equal "$(mysql_query movary "$index_signature_query")" \
    "$(mysql_query doctrine_fresh "$index_signature_query")" \
    'Fresh and converted MySQL indexes differ'

foreign_key_signature_query="SELECT CONCAT_WS(
        '|',
        k.TABLE_NAME,
        k.CONSTRAINT_NAME,
        GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION),
        k.REFERENCED_TABLE_NAME,
        GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.ORDINAL_POSITION),
        r.UPDATE_RULE,
        r.DELETE_RULE
    )
    FROM information_schema.KEY_COLUMN_USAGE k
    INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS r
        ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
        AND r.TABLE_NAME = k.TABLE_NAME
        AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
    WHERE k.TABLE_SCHEMA = DATABASE()
      AND k.REFERENCED_TABLE_NAME IS NOT NULL
    GROUP BY
        k.TABLE_NAME,
        k.CONSTRAINT_NAME,
        k.REFERENCED_TABLE_NAME,
        r.UPDATE_RULE,
        r.DELETE_RULE
    ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME"
assert_equal "$(mysql_query movary "$foreign_key_signature_query")" \
    "$(mysql_query doctrine_fresh "$foreign_key_signature_query")" \
    'Fresh and converted MySQL foreign keys differ'

check_signature_query="SELECT CONCAT_WS('|', tc.TABLE_NAME, tc.CONSTRAINT_NAME, cc.CHECK_CLAUSE)
    FROM information_schema.TABLE_CONSTRAINTS tc
    INNER JOIN information_schema.CHECK_CONSTRAINTS cc
        ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
        AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
    WHERE tc.TABLE_SCHEMA = DATABASE()
      AND tc.CONSTRAINT_TYPE = 'CHECK'
    ORDER BY tc.TABLE_NAME, tc.CONSTRAINT_NAME"
assert_equal "$(mysql_query movary "$check_signature_query")" \
    "$(mysql_query doctrine_fresh "$check_signature_query")" \
    'Fresh and converted MySQL check constraints differ'

assert_equal 'double|3|1' "$(mysql_query doctrine_fresh \
    "SELECT CONCAT_WS('|', DATA_TYPE, NUMERIC_PRECISION, NUMERIC_SCALE)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'movie'
       AND COLUMN_NAME = 'imdb_rating_average'")" \
    'Fresh MySQL Doctrine database does not preserve IMDb rating precision'

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
