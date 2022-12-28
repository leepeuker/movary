<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPrimaryIndexToMovieUserWatchDatesTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_movie_user_watch_dates` (
                `movie_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `watched_at` TEXT DEFAULT NULL,
                `plays` INTEGER DEFAULT 1,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute('INSERT INTO `tmp_movie_user_watch_dates` (movie_id, user_id, watched_at, plays) SELECT * FROM movie_user_watch_dates');
        $this->execute('DROP TABLE `movie_user_watch_dates`');
        $this->execute('ALTER TABLE `tmp_movie_user_watch_dates` RENAME TO `movie_user_watch_dates`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_movie_user_watch_dates` (
                `movie_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `watched_at` TEXT DEFAULT NULL,
                `plays` INTEGER DEFAULT 1,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE,
                PRIMARY KEY (`movie_id`, `user_id`, `watched_at`)
            )
            SQL,
        );
        $this->execute('REPLACE INTO `tmp_movie_user_watch_dates` (movie_id, user_id, watched_at, plays) SELECT * FROM movie_user_watch_dates');
        $this->execute('DROP TABLE `movie_user_watch_dates`');
        $this->execute('ALTER TABLE `tmp_movie_user_watch_dates` RENAME TO `movie_user_watch_dates`');
    }
}
