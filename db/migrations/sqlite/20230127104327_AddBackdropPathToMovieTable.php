<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBackdropPathToMovieTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_tmp` (
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
        $this->execute(
            'INSERT INTO `movie_tmp` (id, title, trakt_id, imdb_id, tmdb_id, letterboxd_id, poster_path, tagline, overview, original_language, runtime, release_date, tmdb_vote_average, tmdb_vote_count, tmdb_poster_path, imdb_rating_average, imdb_rating_vote_count, created_at, updated_at, updated_at_tmdb, updated_at_imdb) 
            SELECT id, title, trakt_id, imdb_id, tmdb_id, letterboxd_id, poster_path, tagline, overview, original_language, runtime, release_date, tmdb_vote_average, tmdb_vote_count, tmdb_poster_path, imdb_rating_average, imdb_rating_vote_count, created_at, updated_at, updated_at_tmdb, updated_at_imdb FROM movie',
        );
        $this->execute('DROP TABLE `movie`');
        $this->execute('ALTER TABLE `movie_tmp` RENAME TO `movie`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_tmp` (
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
                `tmdb_backdrop_path` TEXT DEFAULT NULL,
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
        $this->execute(
            'INSERT INTO `movie_tmp` (id, title, trakt_id, imdb_id, tmdb_id, letterboxd_id, poster_path, tagline, overview, original_language, runtime, release_date, tmdb_vote_average, tmdb_vote_count, tmdb_poster_path, imdb_rating_average, imdb_rating_vote_count, created_at, updated_at, updated_at_tmdb, updated_at_imdb) 
            SELECT * FROM movie',
        );
        $this->execute('DROP TABLE `movie`');
        $this->execute('ALTER TABLE `movie_tmp` RENAME TO `movie`');
    }
}
