init:
	cp docker-compose.override.yml.production docker-compose.override.yml

# Composer
##########
composer_install:
	make exec_app_cmd CMD="composer install --no-dev"
