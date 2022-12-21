<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SetCorrectConstraintForMovieWatchDates extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates DROP PRIMARY KEY, ADD UNIQUE INDEX combinedKey (movie_id, watched_at);
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates DROP CONSTRAINT combinedKey, ADD PRIMARY KEY (movie_id, user_id, watched_at);
            SQL
        );
    }
}
