<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialMigration extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE cache_tmdb_languages;
            DROP TABLE cache_trakt_user_movie_rating;
            DROP TABLE cache_trakt_user_movie_watched;
            DROP TABLE user;
            DROP TABLE company;
            DROP TABLE genre;
            DROP TABLE movie;
            DROP TABLE person;
            DROP TABLE job_queue;
            DROP TABLE movie_cast;
            DROP TABLE movie_crew;
            DROP TABLE movie_genre;
            DROP TABLE movie_production_company;
            DROP TABLE movie_user_rating;
            DROP TABLE movie_user_watch_dates;
            DROP TABLE user_auth_token;
            SQL,
        );
    }

    public function up() : void
    {
        $this->createUserTable();
        $this->createCompanyTable();
        $this->createGenreTable();
        $this->createMovieTable();
        $this->createPersonTable();
        $this->createMovieRelationTables();
        $this->createTraktTables();
        $this->createJobQueueTable();

        $this->execute(
            <<<SQL
            CREATE TABLE `cache_tmdb_languages` (
                `iso_639_1` TEXT,
                `english_name` TEXT NOT NULL,
                PRIMARY KEY (`iso_639_1`)
            )
            SQL,
        );
    }

    private function createCompanyTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `company` (
                `id` INTEGER,
                `name` TEXT NOT NULL,
                `origin_country` TEXT DEFAULT NULL,
                `tmdb_id` INTEGER DEFAULT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`tmdb_id`)
            )
            SQL,
        );
    }

    private function createGenreTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `genre` (
                `id` INTEGER,
                `name` TEXT NOT NULL ,
                `tmdb_id` INTEGER DEFAULT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`name`)
            )
            SQL,
        );
    }

    private function createJobQueueTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `job_queue` (
                `id` INTEGER,
                `job_type` TEXT NOT NULL ,
                `job_status` TEXT NOT NULL,
                `user_id` INTEGER DEFAULT NULL,
                `parameters` TEXT,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE SET NULL 
            )
            SQL,
        );
    }

    private function createMovieRelationTables()
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_cast` (
                `person_id` INTEGER NOT NULL,
                `movie_id` INTEGER NOT NULL,
                `character_name` TEXT NOT NULL,
                `position` INTEGER DEFAULT NULL,
                FOREIGN KEY (`person_id`) REFERENCES person (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `position`)
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_crew` (
                `person_id` INTEGER NOT NULL,
                `movie_id` INTEGER NOT NULL,
                `job` TEXT NOT NULL,
                `department` TEXT NOT NULL,
                `position` INTEGER DEFAULT NULL,
                FOREIGN KEY (`person_id`) REFERENCES person (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `position`)
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_genre` (
                `genre_id` INTEGER NOT NULL,
                `movie_id` INTEGER NOT NULL,
                `position` INTEGER DEFAULT NULL,
                FOREIGN KEY (`genre_id`) REFERENCES genre (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `genre_id`),
                UNIQUE (`movie_id`, `position`)
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_production_company` (
                `company_id` INTEGER NOT NULL,
                `movie_id` INTEGER NOT NULL,
                `position` INTEGER DEFAULT NULL,
                FOREIGN KEY (`company_id`) REFERENCES company (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `company_id`),
                UNIQUE (`movie_id`, `position`)
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_user_rating` (
                `movie_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `rating` INTEGER NOT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_user_watch_dates` (
                `movie_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `watched_at` TEXT NOT NULL,
                `plays` INTEGER DEFAULT 1,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE
            )
            SQL,
        );
    }

    private function createMovieTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `movie` (
                `id` INTEGER,
                `title` TEXT NOT NULL,
                `trakt_id` INTEGER DEFAULT NULL,
                `imdb_id` TEXT DEFAULT NULL,
                `tmdb_id` INTEGER NOT NULL,
                `letterboxd_id` TEXT DEFAULT NULL,
                `poster_path` TEXT DEFAULT NULL,
                `tagline` TEXT DEFAULT NULL,
                `overview` TEXT DEFAULT NULL,
                `original_language` TEXT DEFAULT NULL,
                `runtime` INTEGER DEFAULT NULL,
                `release_date` TEXT DEFAULT NULL,
                `tmdb_vote_average` REAL DEFAULT NULL,
                `tmdb_vote_count` INTEGER DEFAULT NULL,
                `tmdb_poster_path` TEXT DEFAULT NULL,
                `imdb_rating_average` REAL DEFAULT NULL,
                `imdb_rating_vote_count` INTEGER DEFAULT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                `updated_at_tmdb` TEXT DEFAULT NULL,
                `updated_at_imdb` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`trakt_id`),
                UNIQUE (`imdb_id`),
                UNIQUE (`tmdb_id`)
            )
            SQL,
        );
    }

    private function createPersonTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `person` (
                `id` INTEGER,
                `name` TEXT NOT NULL,
                `gender` TEXT NOT NULL,
                `known_for_department` TEXT DEFAULT NULL,
                `poster_path` TEXT DEFAULT NULL,
                `birth_date` TEXT DEFAULT NULL,
                `place_of_birth` TEXT DEFAULT NULL,
                `death_date` TEXT DEFAULT NULL,
                `tmdb_id` INTEGER NOT NULL,
                `tmdb_poster_path` TEXT DEFAULT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                `updated_at_tmdb` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`tmdb_id`)
            )
            SQL,
        );
    }

    private function createTraktTables() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `cache_trakt_user_movie_rating` (
                `trakt_id` INTEGER,
                `user_id` INTEGER,
                `rating` INTEGER DEFAULT NULL,
                `rated_at` TEXT NOT NULL,
                PRIMARY KEY (`trakt_id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE 
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `cache_trakt_user_movie_watched` (
                `trakt_id` INTEGER,
                `user_id` INTEGER,
                `last_updated_at` TEXT NOT NULL,
                PRIMARY KEY (`trakt_id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
    }

    private function createUserTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user` (
                `id` INTEGER,
                `email` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `password` TEXT NOT NULL ,
                `privacy_level` INTEGER DEFAULT 1,
                `date_format_id` INTEGER DEFAULT 0,
                `trakt_user_name` TEXT,
                `plex_webhook_uuid` TEXT,
                `trakt_client_id` TEXT,
                `plex_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_ratings` INTEGER DEFAULT 0,
                `core_account_changes_disabled` INTEGER DEFAULT 0,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`email`), 
                UNIQUE (`name`) 
            )
            SQL,
        );
        $this->execute(
        <<<SQL
            CREATE TABLE `user_auth_token` (
                `id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `token` TEXT NOT NULL,
                `expiration_date` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
    );
    }
}
