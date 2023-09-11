<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SetWatchDateNullableForUsers extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates ADD PRIMARY KEY (movie_id, user_id, watched_at);
            ALTER TABLE movie_user_watch_dates DROP CONSTRAINT unique_watched_dates;
            ALTER TABLE movie_user_watch_dates MODIFY COLUMN watched_at DATE NOT NULL ;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates ADD CONSTRAINT unique_watched_dates UNIQUE (movie_id, user_id, watched_at);
            ALTER TABLE movie_user_watch_dates DROP PRIMARY KEY;
            ALTER TABLE movie_user_watch_dates MODIFY COLUMN watched_at DATE NULL;
            SQL,
        );
    }
}
