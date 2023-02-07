<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPlexServerUrlColumn extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN plex_server_url;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN plex_server_url CHAR(128) DEFAULT NULL AFTER plex_access_token;
            SQL,
        );
    }
}
