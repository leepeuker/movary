<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIsAdminToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_user` (
                `id` INTEGER,
                `email` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `password` TEXT NOT NULL ,
                `privacy_level` INTEGER DEFAULT 1,
                `date_format_id` INTEGER DEFAULT 0,
                `trakt_user_name` TEXT,
                `plex_webhook_uuid` TEXT,
                `jellyfin_webhook_uuid` TEXT,
                `trakt_client_id` TEXT,
                `jellyfin_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_ratings` INTEGER DEFAULT 0,
                `core_account_changes_disabled` INTEGER DEFAULT 0,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`email`), 
                UNIQUE (`name`) 
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `tmp_user` (
                `id`,
                `email`,
                `name`,
                `password`,
                `privacy_level`,
                `date_format_id`,
                `trakt_user_name`,
                `plex_webhook_uuid`,
                `jellyfin_webhook_uuid`,
                `trakt_client_id`,
                `jellyfin_scrobble_views`,
                `plex_scrobble_views`,
                `plex_scrobble_ratings`,
                `core_account_changes_disabled`,
                `created_at`
            ) SELECT
                `id`,
                `email`,
                `name`,
                `password`,
                `privacy_level`,
                `date_format_id`,
                `trakt_user_name`,
                `plex_webhook_uuid`,
                `jellyfin_webhook_uuid`,
                `trakt_client_id`,
                `jellyfin_scrobble_views`,
                `plex_scrobble_views`,
                `plex_scrobble_ratings`,
                `core_account_changes_disabled`,
                `created_at`
            FROM user',
        );
        $this->execute('DROP TABLE `user`');
        $this->execute('ALTER TABLE `tmp_user` RENAME TO `user`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_user` (
                `id` INTEGER,
                `email` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `password` TEXT NOT NULL ,
                `is_admin` TINYINT(1) DEFAULT 0,
                `privacy_level` INTEGER DEFAULT 1,
                `date_format_id` INTEGER DEFAULT 0,
                `trakt_user_name` TEXT,
                `plex_webhook_uuid` TEXT,
                `jellyfin_webhook_uuid` TEXT,
                `trakt_client_id` TEXT,
                `jellyfin_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_ratings` INTEGER DEFAULT 0,
                `core_account_changes_disabled` INTEGER DEFAULT 0,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`email`), 
                UNIQUE (`name`) 
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `tmp_user` (
                `id`,
                `email`,
                `name`,
                `password`,
                `privacy_level`,
                `date_format_id`,
                `trakt_user_name`,
                `plex_webhook_uuid`,
                `jellyfin_webhook_uuid`,
                `trakt_client_id`,
                `jellyfin_scrobble_views`,
                `plex_scrobble_views`,
                `plex_scrobble_ratings`,
                `core_account_changes_disabled`,
                `created_at`
            ) SELECT * FROM user',
        );
        $this->execute('DROP TABLE `user`');
        $this->execute('ALTER TABLE `tmp_user` RENAME TO `user`');
    }
}
