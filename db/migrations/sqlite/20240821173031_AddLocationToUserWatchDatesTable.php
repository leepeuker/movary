<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLocationToUserWatchDatesTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE `location`
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_user_watch_dates_tmp` (
                `movie_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `watched_at` TEXT NOT NULL,
                `plays` INTEGER DEFAULT 1,
                `comment` TEXT DEFAULT NULL,
                `position` INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `movie_user_watch_dates_tmp` (movie_id, user_id, watched_at, plays, comment, position) 
            SELECT movie_id, user_id, watched_at, plays, comment, position  FROM movie_user_watch_dates',
        );
        $this->execute('DROP TABLE `movie_user_watch_dates`');
        $this->execute('ALTER TABLE `movie_user_watch_dates_tmp` RENAME TO `movie_user_watch_dates`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `location` (
                `id` INTEGER NOT NULL,
                `user_id` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_user_watch_dates_tmp` (
                `movie_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `watched_at` TEXT NOT NULL,
                `plays` INTEGER DEFAULT 1,
                `comment` TEXT DEFAULT NULL,
                `position` INTEGER NOT NULL DEFAULT 1,
                `location_id` INTEGER DEFAULT NULL,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE
                FOREIGN KEY (`location_id`) REFERENCES location (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `movie_user_watch_dates_tmp` (movie_id, user_id, watched_at, plays, comment, position   ) 
            SELECT movie_id, user_id, watched_at, plays, comment, position  FROM movie_user_watch_dates',
        );
        $this->execute('DROP TABLE `movie_user_watch_dates`');
        $this->execute('ALTER TABLE `movie_user_watch_dates_tmp` RENAME TO `movie_user_watch_dates`');
    }
}
