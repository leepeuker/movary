<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPlexTokenToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN plex_token;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN plex_token VARCHAR(255) DEFAULT NULL AFTER plex_scrobble_ratings;
            SQL,
        );
    }
}
