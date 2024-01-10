<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPositionToUserWatchDatesTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates DROP COLUMN position;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates ADD COLUMN position SMALLINT DEFAULT 1 NOT NULL AFTER comment;
            SQL,
        );
    }
}
