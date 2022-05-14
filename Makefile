.PHONY:build

include .env

ifeq ($(ENV), development)
    include Makefile.development.mk
else ifneq ($(ENV), development)
	include Makefile.production.mk
endif

# Container management
######################
up:
	mkdir -p tmp/db
	docker-compose up -d

down:
	docker-compose down

reup: down up

build: down init
	docker-compose build --build-arg USER_ID=${USER_ID}

# Container interaction
#######################
exec_php_bash:
	docker exec -it movary-php bash -c "bash"

exec_php_cmd:
	docker exec -i movary-php bash -c "${CMD}"

# Commands
##########
app_sync_all: app_sync_trakt app_sync_tmdb

app_sync_trakt:
	make exec_php_cmd CMD="php bin/console.php app:sync-trakt --overwrite"

app_sync_tmdb:
	make exec_php_cmd CMD="php bin/console.php app:sync-tmdb"

app_sync_letterboxd:
	make exec_php_cmd CMD="php bin/console.php app:sync-letterboxd $(CSV_PATH)"

# Database
##########
db_migration_migrate:
	make exec_php_cmd CMD="vendor/bin/phinx $(PHINX) migrate -c ./settings/phinx.php"

db_migration_rollback:
	make exec_php_cmd CMD="vendor/bin/phinx rollback -c ./settings/phinx.php"
