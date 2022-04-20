init:
	cp docker-compose.override.yml.development docker-compose.override.yml

# Container interaction
#######################
exec_mysql_cli:
	docker exec -it movary-db sh -c "mysql -u${DB_USER} -p${DB_PASSWORD} ${DATABASE_NAME}"

exec_mysql_query:
	docker exec -it movary-db bash -c "mysql -uroot -p${DATABASE_ROOT_PASSWORD} -e \"$(QUERY)\""

# Composer
##########
composer_install:
	make exec_php_cmd CMD="composer install"

composer_update:
	make exec_php_cmd CMD="composer update"

# Database
##########
db_create_database:
	make exec_mysql_query QUERY="DROP DATABASE IF EXISTS $(DATABASE_NAME)"
	make exec_mysql_query QUERY="CREATE DATABASE $(DATABASE_NAME)"
	make exec_mysql_query QUERY="GRANT ALL PRIVILEGES ON $(DATABASE_NAME).* TO $(DATABASE_USER)@'%'"
	make exec_mysql_query QUERY="FLUSH PRIVILEGES;"
	make db_migration_migrate

db_import:
	docker cp $(FILE) movary-db:/tmp/dump.sql
	docker exec movary-db bash -c 'mysql -uroot -p${DATABASE_ROOT_PASSWORD} < /tmp/dump.sql'
	docker exec movary-db bash -c 'rm /tmp/dump.sql'

db_export:
	docker exec movary-db bash -c 'mysqldump --databases --add-drop-database -uroot -p$(DATABASE_ROOT_PASSWORD) $(DATABASE_NAME) > /tmp/dump.sql'
	docker cp movary-db:/tmp/dump.sql tmp/movary-`date +%Y-%m-%d-%H-%M-%S`.sql
	docker exec movary-db bash -c 'rm /tmp/dump.sql'

db_migration_create:
	make exec_php_cmd CMD="vendor/bin/phinx create Migration -c ./settings/phinx.php"

# Tests
#######
test: test_phpcs test_psalm test_phpstan

test_phpcs:
	make exec_php_cmd CMD="vendor/bin/phpcs --standard=./settings/phpcs.xml ./src"

test_phpstan:
	make exec_php_cmd CMD="vendor/bin/phpstan analyse src -c ./settings/phpstan.neon --level 8"

test_psalm:
	make exec_php_cmd CMD="vendor/bin/psalm -c ./settings/psalm.xml --show-info=false"
