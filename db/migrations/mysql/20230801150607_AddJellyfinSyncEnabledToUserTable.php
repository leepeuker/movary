<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddJellyfinSyncEnabledToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN jellyfin_sync_enabled;
            DROP TABLE user_jellyfin_cache;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN `jellyfin_sync_enabled` TINYINT(1) DEFAULT 0 AFTER `jellyfin_server_url`;
            SQL,
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `user_jellyfin_cache` (
                `movary_user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `jellyfin_item_id` VARCHAR(256) NOT NULL,
                `tmdb_id` INT(10) UNSIGNED NOT NULL,
                `watched` TINYINT(1) NOT NULL ,
                PRIMARY KEY (`movary_user_id`, `jellyfin_item_id`),
                FOREIGN KEY (`movary_user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
