<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddJellyfinSyncEnabledToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN jellyfin_sync_enabled;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN jellyfin_sync_enabled TINYINT(1) DEFAULT 0 AFTER jellyfin_server_url;
            SQL,
        );
    }
}
