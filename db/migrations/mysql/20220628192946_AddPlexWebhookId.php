<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPlexWebhookId extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN plex_webhook_uuid;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN plex_webhook_uuid CHAR(36) DEFAULT NULL AFTER password;
            SQL
        );
    }
}
