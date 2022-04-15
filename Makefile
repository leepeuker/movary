.PHONY:build

include .env

ifeq ($(ENV), development)
    include Makefile.database.mk
endif

# Docker
########
PHP_CONTAINER_NAME = movary-php
PHP_DOCKER_OPTIONS = --rm \
	             --network=host \
                 --user $(USER_ID):$(USER_ID) \
                 -v ${PWD}:/app
PHP_DOCKER_RUN = docker run $(PHP_DOCKER_OPTIONS) $(PHP_CONTAINER_NAME)

# Setup
#######
build:
	docker build -t $(PHP_CONTAINER_NAME) ./build/php/
	$(MAKE) composer_install

run_php_bash:
	docker run -it $(PHP_DOCKER_OPTIONS) $(PHP_CONTAINER_NAME) bash

run_php_cmd:
	$(PHP_DOCKER_RUN) bash -c "${CMD}"

# Composer
##########
composer_install:
	$(PHP_DOCKER_RUN) composer install

composer_update:
	$(PHP_DOCKER_RUN) composer update

# Commands
##########
app_sync_all: app_sync_trakt app_sync_tmdb

app_sync_trakt:
	make run_php_cmd CMD="php bin/console.php app:sync-trakt"

app_sync_tmdb:
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

# Database
##########
db_migration_migrate:
	make run_php_cmd CMD="vendor/bin/phinx $(PHINX) migrate -c ./settings/phinx.php -e $(ENV)"

db_migration_rollback:
	make run_php_cmd CMD="vendor/bin/phinx rollback -c ./settings/phinx.php -e $(ENV)"

db_migration_create:
	make run_php_cmd CMD="vendor/bin/phinx create Migration -c ./settings/phinx.php"
