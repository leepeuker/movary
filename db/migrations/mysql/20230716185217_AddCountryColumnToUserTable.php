<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCountryColumnToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN country;
            ALTER TABLE user CHANGE COLUMN watchlist_automatic_removal_enabled watchlist_automatic_removal_enabled TINYINT DEFAULT 0 NOT NULL AFTER plex_scrobble_ratings;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN country CHAR(2) DEFAULT NULL AFTER watchlist_automatic_removal_enabled;
            ALTER TABLE user CHANGE COLUMN watchlist_automatic_removal_enabled watchlist_automatic_removal_enabled TINYINT DEFAULT 1 NOT NULL AFTER plex_scrobble_ratings;
            SQL,
        );
    }
}
