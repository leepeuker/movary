init:
	cp docker-compose.override.yml.development docker-compose.override.yml

# Container interaction
#######################
exec_mysql_cli:
	docker-compose exec mysql sh -c "mysql -u${DB_USER} -p${DB_PASSWORD} ${DATABASE_NAME}"

exec_mysql_query:
	docker-compose exec mysql bash -c "mysql -uroot -p${DATABASE_ROOT_PASSWORD} -e \"$(QUERY)\""

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
	docker-compose exec mysql bash -c 'mysql -uroot -p${DATABASE_ROOT_PASSWORD} < /tmp/host/dump.sql'

db_export:
	docker-compose exec mysql bash -c 'mysqldump --databases --add-drop-database -uroot -p$(DATABASE_ROOT_PASSWORD) $(DATABASE_NAME) > /tmp/host/dump.sql'
	sudo chown $(USER_ID):$(USER_ID) tmp/dump.sql

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
