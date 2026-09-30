<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeColumnDefinitionsBeforeDoctrineCutover extends AbstractMigration
{
    public function down() : void
    {
        throw new RuntimeException('The MySQL column definition normalization is irreversible.');
    }

    public function up() : void
    {
        $timeZoneRow = $this->fetchRow('SELECT @@SESSION.time_zone AS session_time_zone');
        $originalTimeZone = $timeZoneRow === false ? 'SYSTEM' : (string)$timeZoneRow['session_time_zone'];

        $this->execute("SET SESSION time_zone = '+00:00'");

        try {
            $this->execute(
                <<<'SQL'
                ALTER TABLE cache_trakt_user_movie_rating
                    MODIFY rating SMALLINT UNSIGNED DEFAULT NULL,
                    MODIFY rated_at DATETIME NOT NULL;

                ALTER TABLE company
                    MODIFY created_at DATETIME NOT NULL,
                    MODIFY updated_at DATETIME DEFAULT NULL;

                ALTER TABLE country
                    MODIFY updated_at DATETIME DEFAULT NULL;

                ALTER TABLE genre
                    MODIFY created_at DATETIME NOT NULL,
                    MODIFY updated_at DATETIME DEFAULT NULL;

                ALTER TABLE job_queue
                    MODIFY created_at DATETIME NOT NULL,
                    MODIFY updated_at DATETIME DEFAULT NULL;

                ALTER TABLE location
                    MODIFY updated_at DATETIME DEFAULT NULL;

                ALTER TABLE movie
                    MODIFY created_at DATETIME NOT NULL,
                    MODIFY updated_at DATETIME DEFAULT NULL,
                    MODIFY updated_at_imdb DATETIME DEFAULT NULL;

                ALTER TABLE movie_production_countries
                    MODIFY position SMALLINT NOT NULL;

                ALTER TABLE movie_user_rating
                    MODIFY rating SMALLINT NOT NULL,
                    MODIFY created_at DATETIME NOT NULL,
                    MODIFY updated_at DATETIME DEFAULT NULL;

                ALTER TABLE person
                    MODIFY gender SMALLINT UNSIGNED NOT NULL,
                    MODIFY created_at DATETIME NOT NULL,
                    MODIFY updated_at DATETIME DEFAULT NULL;

                ALTER TABLE user
                    MODIFY jellyfin_scrobble_views SMALLINT NOT NULL DEFAULT 1,
                    MODIFY emby_scrobble_views SMALLINT NOT NULL DEFAULT 1,
                    MODIFY kodi_scrobble_views SMALLINT NOT NULL DEFAULT 1,
                    MODIFY privacy_level SMALLINT UNSIGNED DEFAULT 1,
                    MODIFY date_format_id SMALLINT UNSIGNED DEFAULT 0,
                    MODIFY plex_scrobble_views SMALLINT NOT NULL DEFAULT 1,
                    MODIFY plex_scrobble_ratings SMALLINT NOT NULL DEFAULT 0,
                    MODIFY watchlist_automatic_removal_enabled SMALLINT NOT NULL DEFAULT 1,
                    MODIFY display_tmdb_rating SMALLINT NOT NULL DEFAULT 1,
                    MODIFY display_imdb_rating SMALLINT NOT NULL DEFAULT 1,
                    MODIFY mastodon_enabled SMALLINT NOT NULL DEFAULT 0,
                    MODIFY mastodon_post_automatic SMALLINT NOT NULL DEFAULT 1,
                    MODIFY core_account_changes_disabled SMALLINT UNSIGNED DEFAULT 0,
                    MODIFY created_at DATETIME NOT NULL;

                ALTER TABLE user_auth_token
                    MODIFY created_at DATETIME NOT NULL;

                ALTER TABLE watchlist
                    MODIFY added_at DATETIME NOT NULL;
                SQL,
            );
        } finally {
            $escapedTimeZone = str_replace("'", "''", $originalTimeZone);
            $this->execute("SET SESSION time_zone = '$escapedTimeZone'");
        }
    }
}
