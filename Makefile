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

run_cmd_php:
	docker exec -i movary-php bash -c "${CMD}"

run_cmd_mysql:
	docker exec -it movary-mysql bash -c "mysql -uroot -p${MYSQL_ROOT_PASSWORD} -e \"$(QUERY)\""

# Database
##########
db_create_database:
	make run_cmd_mysql QUERY="DROP DATABASE IF EXISTS $(DB_NAME)"
	make run_cmd_mysql QUERY="CREATE DATABASE $(DB_NAME)"
	make run_cmd_mysql QUERY="GRANT ALL PRIVILEGES ON $(DB_NAME).* TO $(MYSQL_USER)@'%'"
	make run_cmd_mysql QUERY="FLUSH PRIVILEGES;"
	make db_migration_migrate

db_migration_migrate:
	make run_cmd_php CMD="vendor/bin/phinx $(PHINX) migrate -c ./settings/phinx.php -e $(ENV)"

db_migration_rollback:
	make run_cmd_php CMD="vendor/bin/phinx rollback -c ./settings/phinx.php -e $(ENV)"

db_migration_create:
	make run_cmd_php CMD="vendor/bin/phinx create Migration -c ./settings/phinx.php"

db_import:
	docker cp $(FILE) movary-mysql:/tmp/dump.sql
	docker exec movary-mysql bash -c 'mysql -uroot -p${MYSQL_ROOT_PASSWORD} < /tmp/dump.sql'
	docker exec movary-mysql bash -c 'rm /tmp/dump.sql'

db_export:
	docker exec movary-mysql bash -c 'mysqldump --databases --no-tablespaces --add-drop-database -u$(DB_USER) -p$(DB_PASSWORD) $(DB_NAME) > /tmp/dump.sql'
	docker cp movary-mysql:/tmp/dump.sql tmp/movary-`date +%Y-%m-%d-%H-%M-%S`.sql
	docker exec movary-mysql bash -c 'rm /tmp/dump.sql'

# Composer
##########
composer_install:
	docker exec movary-php bash -c "composer install"

composer_update:
	docker exec movary-php bash -c "composer update"

# Commands
##########
sync_trakt:
	make run_cmd_php CMD="php bin/console.php app:sync-trakt"

sync_tmdb:
	make run_cmd_php CMD="php bin/console.php app:sync-tmdb"


# Tests
#######
test: test_psalm test_phpstan

test_phpstan:
	make run_cmd_php CMD="vendor/bin/phpstan analyse src -c ./settings/phpstan.neon --level max"

test_psalm:
	make run_cmd_php CMD="vendor/bin/psalm -c ./settings/psalm.xml --show-info=false"
