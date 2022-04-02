<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPerson extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `movie_crew`');
        $this->execute('DROP TABLE `movie_cast`');
        $this->execute('DROP TABLE `person`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `person` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(256) NOT NULL,
                `gender` ENUM('0', '1', '2', '3') NOT NULL,
                `popularity` FLOAT (6,3),
                `known_for_department` VARCHAR(256),
                `tmdb_id` INT(10) UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE NOW(),
                PRIMARY KEY (`id`),
                UNIQUE (`tmdb_id`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `movie_cast` (
                `person_id` INT(10) UNSIGNED NOT NULL,
                `movie_id` INT(10) UNSIGNED NOT NULL,
                `character_name` VARCHAR(256) NOT NULL,
                `position` SMALLINT UNSIGNED,
                FOREIGN KEY (`person_id`) REFERENCES person (`id`),
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`),
                UNIQUE (`movie_id`, `position`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `movie_crew` (
                `person_id` INT(10) UNSIGNED NOT NULL,
                `movie_id` INT(10) UNSIGNED NOT NULL,
                `job` VARCHAR(256) NOT NULL,
                `department` VARCHAR(256) NOT NULL,
                `position` SMALLINT UNSIGNED,
                FOREIGN KEY (`person_id`) REFERENCES person (`id`),
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`),
                UNIQUE (`movie_id`, `position`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }
}
