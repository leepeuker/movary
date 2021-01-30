<?php

use Phinx\Migration\AbstractMigration;

class SetupBaseTables extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `cache_trakt_user_movie_rating`');
        $this->execute('DROP TABLE `cache_trakt_user_movie_watched`');
        $this->execute('DROP TABLE `movie_history`');
        $this->execute('DROP TABLE `movie`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `movie` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(256) NOT NULL,
                `year` YEAR NOT NULL,
                `rating` TINYINT UNSIGNED DEFAULT NULL,
                `trakt_id` INT(10) UNSIGNED NOT NULL,
                `imdb_id` VARCHAR(10) NOT NULL,
                `tmdb_id` INT(10) UNSIGNED NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT NOW(),
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE NOW(),
                PRIMARY KEY (`id`),
                UNIQUE (`trakt_id`),
                UNIQUE (`imdb_id`),
                UNIQUE (`tmdb_id`)
            ) COLLATE="utf8mb4_general_ci" ENGINE=InnoDB
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `movie_history` (
                `movie_id` INT(10) UNSIGNED NOT NULL,
                `watched_at` DATETIME NOT NULL,
                FOREIGN KEY (`movie_id`) REFERENCES `movie`(`id`)
            ) COLLATE="utf8mb4_general_ci" ENGINE=InnoDB
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `cache_trakt_user_movie_rating` (
                `trakt_id` INT(10) UNSIGNED NOT NULL,
                `rating` TINYINT UNSIGNED,
                `rated_at` TIMESTAMP NOT NULL,
                PRIMARY KEY (`trakt_id`)
            ) COLLATE="utf8mb4_general_ci" ENGINE=InnoDB
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `cache_trakt_user_movie_watched` (
                `trakt_id` INT(10) UNSIGNED NOT NULL,
                `last_updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`trakt_id`)
            ) COLLATE="utf8mb4_general_ci" ENGINE=InnoDB
            SQL
        );
    }
}
