<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

// I don't think this works ? MySql is confusing.

final class AddCreatedAtToUserWatchDatesTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE `movie_user_watch_dates` 
            DROP COLUMN `created_at`;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE `movie_user_watch_dates` 
                ADD COLUMN `created_at` TEXT NOT NULL DEFAULT TIMESTAMP AFTER `location_id`,
            SQL
        );
    }
}
