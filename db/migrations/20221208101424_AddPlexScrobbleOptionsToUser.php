<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPlexScrobbleOptionsToUser extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN plex_scrobble_views;
            ALTER TABLE user DROP COLUMN plex_scrobble_ratings;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN plex_scrobble_views TINYINT DEFAULT 1 NOT NULL AFTER trakt_client_id;
            ALTER TABLE user ADD COLUMN plex_scrobble_ratings TINYINT DEFAULT 0 NOT NULL AFTER plex_scrobble_views;
            SQL,
        );
    }
}
