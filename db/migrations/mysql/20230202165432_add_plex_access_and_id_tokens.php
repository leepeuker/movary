<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPlexAccessAndIdTokens extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN plex_client_id;
            ALTER TABLE user DROP COLUMN plex_client_temporary_code;
            ALTER TABLE user DROP COLUMN plex_access_token;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN plex_client_id CHAR(64) DEFAULT NULL AFTER trakt_client_id;
            ALTER TABLE user ADD COLUMN plex_client_temporary_code CHAR(64) DEFAULT NULL AFTER plex_client_id;
            ALTER TABLE user ADD COLUMN plex_access_token CHAR(128) DEFAULT NULL AFTER plex_client_temporary_code;
            SQL,
        );
    }
}
