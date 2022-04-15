.PHONY: build

include .env

# Docker
########
build:
	docker-compose build --no-cache --build-arg USER_ID=${USER_ID}

up:
	docker-compose up -d

down:
	docker-compose down

reup: down up

connect_php_bash:
	docker exec -it movary-php bash

run_php_cmd:
	docker exec -i movary-php bash -c "${CMD}"

run_mysql_cmd:
	docker exec -i movary-mysql bash -c "${CMD}"

run_mysql_query:
	make run_mysql_cmd CMD="mysql -uroot -p${MYSQL_ROOT_PASSWORD} -e \\\"$(QUERY)\\\""

# Database
##########
db_create_database:
	make run_mysql_query QUERY="DROP DATABASE IF EXISTS $(DB_NAME)"
	make run_mysql_query QUERY="CREATE DATABASE $(DB_NAME)"
	make run_mysql_query QUERY="GRANT ALL PRIVILEGES ON $(DB_NAME).* TO $(MYSQL_USER)@'%'"
	make run_mysql_query QUERY="FLUSH PRIVILEGES;"
	make db_migration_migrate

db_migration_migrate:
	make run_php_cmd CMD="vendor/bin/phinx $(PHINX) migrate -c ./settings/phinx.php -e $(ENV)"

db_migration_rollback:
	make run_php_cmd CMD="vendor/bin/phinx rollback -c ./settings/phinx.php -e $(ENV)"

db_migration_create:
	make run_php_cmd CMD="vendor/bin/phinx create Migration -c ./settings/phinx.php"

db_import:
	docker cp $(FILE) movary-mysql:/tmp/dump.sql
	make run_mysql_cmd CMD="mysql -uroot -p${MYSQL_ROOT_PASSWORD} < /tmp/dump.sql"
	make run_mysql_cmd CMD="rm /tmp/dump.sql"

db_export:
	make run_mysql_cmd CMD="mysqldump --databases --no-tablespaces --add-drop-database -u$(DB_USER) -p$(DB_PASSWORD) $(DB_NAME) > /tmp/dump.sql"
	docker cp movary-mysql:/tmp/dump.sql tmp/movary-`date +%Y-%m-%d-%H-%M-%S`.sql
	make run_mysql_cmd CMD="rm /tmp/dump.sql"

# Composer
##########
composer_install:
	make run_php_cmd CMD="composer install"

composer_update:
	make run_php_cmd CMD="composer update"

# Commands
##########
sync: sync_trakt sync_tmdb

sync_trakt:
	make run_php_cmd CMD="php bin/console.php app:sync-trakt"

sync_tmdb:
	make run_php_cmd CMD="php bin/console.php app:sync-tmdb"

# Tests
#######
test: test_phpcs test_psalm test_phpstan

test_phpcs:
	make run_php_cmd CMD="vendor/bin/phpcs --standard=./settings/phpcs.xml ./src"

test_phpstan:
	make run_php_cmd CMD="vendor/bin/phpstan analyse src -c ./settings/phpstan.neon --level 8"

test_psalm:
	make run_php_cmd CMD="vendor/bin/psalm -c ./settings/psalm.xml --show-info=false"
