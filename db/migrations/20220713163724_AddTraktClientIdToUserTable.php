<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddTraktClientIdToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN trakt_user_name;
            ALTER TABLE user DROP COLUMN trakt_client_id;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN trakt_user_name VARCHAR(255) DEFAULT NULL AFTER plex_webhook_uuid;
            ALTER TABLE user ADD COLUMN trakt_client_id VARCHAR(255) DEFAULT NULL AFTER trakt_user_name;
            SQL
        );
    }
}
