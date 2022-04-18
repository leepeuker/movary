<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProductionCompanies extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `movie_production_company`');
        $this->execute('DROP TABLE `company`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `company` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(256) NOT NULL,
                `origin_country` CHAR(2) DEFAULT NULL,
                `tmdb_id` INT(10) UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE NOW(),
                PRIMARY KEY (`id`),
                UNIQUE (`name`, `origin_country`),
                UNIQUE (`tmdb_id`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `movie_production_company` (
                `company_id` INT(10) UNSIGNED NOT NULL,
                `movie_id` INT(10) UNSIGNED NOT NULL,
                `position` SMALLINT UNSIGNED,
                FOREIGN KEY (`company_id`) REFERENCES company (`id`),
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`),
                UNIQUE (`company_id`, `movie_id`),
                UNIQUE (`movie_id`, `position`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }
}
