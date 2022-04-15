# Docker
########
DB_CONTAINER_NAME = movary-db

# Setup
#######
run_mysql:
	docker run --rm -d \
        --name=$(DB_CONTAINER_NAME) \
        -e MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD} \
        -e MYSQL_USER=${MYSQL_USER} \
        -e MYSQL_PASSWORD=${MYSQL_PASSWORD} \
        -p ${MYSQL_PORT}:3306 \
        -v ${PWD}/tmp/db/:/var/lib/mysql \
        mysql:5.7

stop_mysql:
	docker container stop $(DB_CONTAINER_NAME)

run_mysql_bash:
	docker exec -it $(DB_CONTAINER_NAME) bash

run_mysql_cmd:
	docker exec -i $(DB_CONTAINER_NAME) bash -c "${CMD}"

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
	docker cp $(FILE) $(DB_CONTAINER_NAME):/tmp/dump.sql
	make run_mysql_cmd CMD="mysql -uroot -p${MYSQL_ROOT_PASSWORD} < /tmp/dump.sql"
	make run_mysql_cmd CMD="rm /tmp/dump.sql"

db_export:
	make run_mysql_cmd CMD="mysqldump --databases --no-tablespaces --add-drop-database -u$(DB_USER) -p$(DB_PASSWORD) $(DB_NAME) > /tmp/dump.sql"
	docker cp $(DB_CONTAINER_NAME):/tmp/dump.sql tmp/movary-`date +%Y-%m-%d-%H-%M-%S`.sql
	make run_mysql_cmd CMD="rm /tmp/dump.sql"
