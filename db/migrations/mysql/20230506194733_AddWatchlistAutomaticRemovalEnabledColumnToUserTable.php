<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWatchlistAutomaticRemovalEnabledColumnToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN watchlist_automatic_removal_enabled;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN watchlist_automatic_removal_enabled TINYINT DEFAULT 0 NOT NULL AFTER plex_scrobble_ratings;
            SQL,
        );
    }
}
