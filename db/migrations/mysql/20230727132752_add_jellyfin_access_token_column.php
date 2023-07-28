<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddJellyfinAccessTokenColumn extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN jellyfin_access_token;
            ALTER TABLE user DROP COLUMN jellyfin_user_id;
            ALTER TABLE user DROP COLUMN jellyfin_server_url;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN jellyfin_access_token CHAR(128) DEFAULT NULL AFTER dashboard_order_rows;
            ALTER TABLE user ADD COLUMN jellyfin_user_id CHAR(128) DEFAULT NULL AFTER jellyfin_access_token;
            ALTER TABLE user ADD COLUMN jellyfin_server_url CHAR(128) DEFAULT NULL AFTER jellyfin_user_id;
            SQL,
        );
    }
}
