<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddJellyfinWebhookId extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN jellyfin_webhook_uuid;
            ALTER TABLE user DROP COLUMN jellyfin_scrobble_views;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN jellyfin_webhook_uuid CHAR(36) DEFAULT NULL AFTER password;
            ALTER TABLE user ADD COLUMN jellyfin_scrobble_views TINYINT DEFAULT 1 NOT NULL AFTER jellyfin_webhook_uuid;
            SQL,
        );
    }
}
