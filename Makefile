.PHONY:build

include .env

# Container management
######################
up:
	mkdir -p tmp/db
	docker-compose up -d

down:
	docker-compose down

reup: down up

build: down
	docker-compose build --no-cache
	make up
	make composer_install
	make app_database_migrate

# Container interaction
#######################
exec_app_bash:
	docker-compose exec app bash

exec_app_cmd:
	docker-compose exec app bash -c "${CMD}"

exec_mysql_cli:
	docker-compose exec mysql sh -c "mysql -u${DB_USER} -p${DB_PASSWORD} ${DATABASE_NAME}"

exec_mysql_query:
	docker-compose exec mysql bash -c "mysql -uroot -p${DATABASE_ROOT_PASSWORD} -e \"$(QUERY)\""

# Composer
##########
composer_install:
	make exec_app_cmd CMD="composer install"

composer_update:
	make exec_app_cmd CMD="composer update"

# Database
##########
db_create_database:
	make exec_mysql_query QUERY="DROP DATABASE IF EXISTS $(DATABASE_NAME)"
	make exec_mysql_query QUERY="CREATE DATABASE $(DATABASE_NAME)"
	make exec_mysql_query QUERY="GRANT ALL PRIVILEGES ON $(DATABASE_NAME).* TO $(DATABASE_USER)@'%'"
	make exec_mysql_query QUERY="FLUSH PRIVILEGES;"
	make app_database_migrate

db_import:
	docker-compose exec mysql bash -c 'mysql -uroot -p${DATABASE_ROOT_PASSWORD} < /tmp/host/dump.sql'

db_export:
	docker-compose exec mysql bash -c 'mysqldump --databases --add-drop-database -uroot -p$(DATABASE_ROOT_PASSWORD) $(DATABASE_NAME) > /tmp/host/dump.sql'
	sudo chown $(USER_ID):$(USER_ID) tmp/dump.sql

db_migration_create:
	make exec_app_cmd CMD="vendor/bin/phinx create Migration -c ./settings/phinx.php"

# App commands
##############
app_sync_all: app_sync_trakt app_sync_tmdb

app_database_migrate:
	make exec_app_cmd CMD="php bin/console.php movary:database:migration --migrate"

app_database_rollback:
	make exec_app_cmd CMD="php bin/console.php movary:database:migration --rollback"

app_sync_trakt:
	make exec_app_cmd CMD="php bin/console.php movary:sync-trakt --overwrite"

app_sync_tmdb:
	make exec_app_cmd CMD="php bin/console.php movary:sync-tmdb"

app_sync_letterboxd:
	make exec_app_cmd CMD="php bin/console.php movary:sync-letterboxd $(CSV_PATH)"

# Tests
#######
test: test_phpcs test_psalm test_phpstan

test_phpcs:
	make exec_app_cmd CMD="vendor/bin/phpcs --standard=./settings/phpcs.xml"

test_phpstan:
	make exec_app_cmd CMD="vendor/bin/phpstan analyse -c ./settings/phpstan.neon"

test_psalm:
	make exec_app_cmd CMD="vendor/bin/psalm -c ./settings/psalm.xml --show-info=false"
