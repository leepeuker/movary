<?php

use Phinx\Migration\AbstractMigration;

class AddGenre extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `movie_genre`');
        $this->execute('DROP TABLE `genre`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `genre` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(256) NOT NULL,
                `tmdb_id` INT(10) UNSIGNED DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE NOW(),
                PRIMARY KEY (`id`),
                UNIQUE (`name`),
                UNIQUE (`tmdb_id`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `movie_genre` (
                `genre_id` INT(10) UNSIGNED NOT NULL,
                `movie_id` INT(10) UNSIGNED NOT NULL,
                `position` SMALLINT UNSIGNED,
                UNIQUE (`genre_id`, `movie_id`),
                UNIQUE (`movie_id`, `position`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }
}
