<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLocationsFeatureFlagToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_user` (
                `id` INTEGER,
                `email` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `password` TEXT NOT NULL,
                `totp_uri` TEXT DEFAULT NULL,
                `is_admin` TINYINT(1) DEFAULT 0,
                `dashboard_visible_rows` TEXT DEFAULT NULL,
                `dashboard_extended_rows` TEXT DEFAULT NULL,
                `dashboard_order_rows` TEXT DEFAULT NULL,
                `jellyfin_access_token` TEXT DEFAULT NULL,
                `jellyfin_user_id` TEXT DEFAULT NULL,
                `jellyfin_server_url` TEXT DEFAULT NULL,
                `jellyfin_sync_enabled` TINYINT(1) DEFAULT 0,
                `privacy_level` INTEGER DEFAULT 1,
                `date_format_id` INTEGER DEFAULT 0,
                `trakt_user_name` TEXT,
                `plex_webhook_uuid` TEXT,
                `jellyfin_webhook_uuid` TEXT,
                `emby_webhook_uuid` TEXT,
                `trakt_client_id` TEXT,
                `plex_client_id` TEXT DEFAULT NULL,
                `plex_client_temporary_code` TEXT DEFAULT NULL,
                `plex_access_token` TEXT DEFAULT NULL,
                `plex_account_id` TEXT DEFAULT NULL,
                `plex_server_url` TEXT DEFAULT NULL,
                `jellyfin_scrobble_views` INTEGER DEFAULT 1,
                `emby_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_ratings` INTEGER DEFAULT 0,
                `radarr_feed_uuid` TEXT DEFAULT NULL,
                `watchlist_automatic_removal_enabled` INTEGER DEFAULT 1,
                `country` TEXT DEFAULT NULL,
                `display_character_names` INTEGER DEFAULT 1,
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
                `totp_uri`,
                `is_admin`,
                `dashboard_visible_rows`,
                `dashboard_extended_rows`,
                `dashboard_order_rows`,
                `jellyfin_access_token`,
                `jellyfin_user_id`,
                `jellyfin_server_url`,
                `privacy_level`,
                `date_format_id`,
                `trakt_user_name`,
                `plex_webhook_uuid`,
                `jellyfin_webhook_uuid`,
                `emby_webhook_uuid`,
                `trakt_client_id`,
                `plex_client_id`,
                `plex_client_temporary_code`,
                `plex_access_token`,
                `plex_account_id`,
                `plex_server_url`,
                `jellyfin_scrobble_views`,
                `emby_scrobble_views`,
                `plex_scrobble_views`,
                `plex_scrobble_ratings`,
                `radarr_feed_uuid`,
                `watchlist_automatic_removal_enabled`,
                `country`,
                `display_character_names`,
                `core_account_changes_disabled`,
                `created_at`
                ) SELECT 
                    `id`,
                    `email`,
                    `name`,
                    `password`,
                    `totp_uri`,
                    `is_admin`,
                    `dashboard_visible_rows`,
                    `dashboard_extended_rows`,
                    `dashboard_order_rows`,
                    `jellyfin_access_token`,
                    `jellyfin_user_id`,
                    `jellyfin_server_url`,
                    `privacy_level`,
                    `date_format_id`,
                    `trakt_user_name`,
                    `plex_webhook_uuid`,
                    `jellyfin_webhook_uuid`,
                    `emby_webhook_uuid`,
                    `trakt_client_id`,
                    `plex_client_id`,
                    `plex_client_temporary_code`,
                    `plex_access_token`,
                    `plex_account_id`,
                    `plex_server_url`,
                    `jellyfin_scrobble_views`,
                    `emby_scrobble_views`,
                    `plex_scrobble_views`,
                    `plex_scrobble_ratings`,
                    `radarr_feed_uuid`,
                    `watchlist_automatic_removal_enabled`,
                    `country`,
                    `display_character_names`,
                    `core_account_changes_disabled`,
                    `created_at` FROM user',
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
                `password` TEXT NOT NULL,
                `totp_uri` TEXT DEFAULT NULL,
                `is_admin` TINYINT(1) DEFAULT 0,
                `dashboard_visible_rows` TEXT DEFAULT NULL,
                `dashboard_extended_rows` TEXT DEFAULT NULL,
                `dashboard_order_rows` TEXT DEFAULT NULL,
                `jellyfin_access_token` TEXT DEFAULT NULL,
                `jellyfin_user_id` TEXT DEFAULT NULL,
                `jellyfin_server_url` TEXT DEFAULT NULL,
                `jellyfin_sync_enabled` TINYINT(1) DEFAULT 0,
                `privacy_level` INTEGER DEFAULT 1,
                `date_format_id` INTEGER DEFAULT 0,
                `trakt_user_name` TEXT,
                `plex_webhook_uuid` TEXT,
                `jellyfin_webhook_uuid` TEXT,
                `emby_webhook_uuid` TEXT,
                `trakt_client_id` TEXT,
                `plex_client_id` TEXT DEFAULT NULL,
                `plex_client_temporary_code` TEXT DEFAULT NULL,
                `plex_access_token` TEXT DEFAULT NULL,
                `plex_account_id` TEXT DEFAULT NULL,
                `plex_server_url` TEXT DEFAULT NULL,
                `jellyfin_scrobble_views` INTEGER DEFAULT 1,
                `emby_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_views` INTEGER DEFAULT 1,
                `plex_scrobble_ratings` INTEGER DEFAULT 0,
                `radarr_feed_uuid` TEXT DEFAULT NULL,
                `watchlist_automatic_removal_enabled` INTEGER DEFAULT 1,
                `country` TEXT DEFAULT NULL,
                `display_character_names` INTEGER DEFAULT 1,
                `locations_enabled` INTEGER DEFAULT 1,
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
                `totp_uri`,
                `is_admin`,
                `dashboard_visible_rows`,
                `dashboard_extended_rows`,
                `dashboard_order_rows`,
                `jellyfin_access_token`,
                `jellyfin_user_id`,
                `jellyfin_server_url`,
                `jellyfin_sync_enabled`,
                `privacy_level`,
                `date_format_id`,
                `trakt_user_name`,
                `plex_webhook_uuid`,
                `jellyfin_webhook_uuid`,
                `emby_webhook_uuid`,
                `trakt_client_id`,
                `plex_client_id`,
                `plex_client_temporary_code`,
                `plex_access_token`,
                `plex_account_id`,
                `plex_server_url`,
                `jellyfin_scrobble_views`,
                `emby_scrobble_views`,
                `plex_scrobble_views`,
                `plex_scrobble_ratings`,
                `radarr_feed_uuid`,
                `watchlist_automatic_removal_enabled`,
                `country`,
                `display_character_names`,
                `core_account_changes_disabled`,
                `created_at`
                ) SELECT * FROM user',
        );
        $this->execute('DROP TABLE `user`');
        $this->execute('ALTER TABLE `tmp_user` RENAME TO `user`');
    }
}
