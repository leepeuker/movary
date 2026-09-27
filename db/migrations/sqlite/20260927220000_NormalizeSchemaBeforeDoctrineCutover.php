<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeSchemaBeforeDoctrineCutover extends AbstractMigration
{
    public function down() : void
    {
        throw new RuntimeException('The Doctrine cutover schema normalization is irreversible.');
    }

    public function init() : void
    {
        $this->execute('PRAGMA foreign_keys = OFF');
    }

    public function up() : void
    {
        $this->backfillUserFlags();
        $this->runPreflightChecks();
        $this->normalizeCacheTables();
        $this->normalizePersonTable();
        $this->normalizeUserTable();
        $this->normalizeMovieTables();
        $this->normalizeAuthenticationTable();
        $this->addIndexes();

        if ($this->fetchAll('PRAGMA foreign_key_check') !== []) {
            throw new RuntimeException('Cannot normalize the SQLite schema: foreign-key violations exist.');
        }
    }

    private function runPreflightChecks() : void
    {
        $checks = [
            [
                'SELECT iso_639_1 FROM cache_tmdb_languages WHERE iso_639_1 IS NULL LIMIT 1',
                'cache_tmdb_languages.iso_639_1 contains null.',
            ],
            [
                'SELECT trakt_id FROM cache_trakt_user_movie_rating WHERE user_id IS NULL LIMIT 1',
                'cache_trakt_user_movie_rating.user_id contains null.',
            ],
            [
                'SELECT trakt_id FROM cache_trakt_user_movie_watched WHERE user_id IS NULL LIMIT 1',
                'cache_trakt_user_movie_watched.user_id contains null.',
            ],
            [
                'SELECT tmdb_id FROM genre GROUP BY tmdb_id HAVING COUNT(*) > 1 LIMIT 1',
                'genre.tmdb_id contains duplicate values.',
            ],
            [
                'SELECT movie_id FROM movie_production_countries GROUP BY movie_id, iso_3166_1 HAVING COUNT(*) > 1 LIMIT 1',
                'movie_production_countries contains duplicate movie/country pairs.',
            ],
            [
                'SELECT movie_id FROM movie_user_rating GROUP BY movie_id, user_id HAVING COUNT(*) > 1 LIMIT 1',
                'movie_user_rating contains duplicate movie/user pairs.',
            ],
            [
                'SELECT movie_id FROM movie_user_watch_dates WHERE watched_at IS NOT NULL GROUP BY movie_id, user_id, watched_at HAVING COUNT(*) > 1 LIMIT 1',
                'movie_user_watch_dates contains duplicate dated entries.',
            ],
            [
                'SELECT token FROM user_auth_token GROUP BY token HAVING COUNT(*) > 1 LIMIT 1',
                'user_auth_token.token contains duplicate values.',
            ],
            [
                'SELECT id FROM person WHERE gender NOT IN (0, 1, 2, 3) LIMIT 1',
                'person.gender contains an unsupported value.',
            ],
            [
                "SELECT id FROM user WHERE mastodon_post_visibility NOT IN ('public', 'private', 'unlisted', 'direct') LIMIT 1",
                'user.mastodon_post_visibility contains an unsupported value.',
            ],
            [
                'SELECT id FROM user_auth_token WHERE LENGTH(token) > 32 LIMIT 1',
                'user_auth_token.token contains a token longer than 32 characters.',
            ],
            [
                'SELECT id FROM location WHERE user_id IS NULL OR CAST(user_id AS INTEGER) < 1 LIMIT 1',
                'location.user_id contains a value that cannot be normalized to an integer user ID.',
            ],
            [
                'SELECT movie_id FROM movie_production_countries WHERE CAST(movie_id AS INTEGER) < 1 LIMIT 1',
                'movie_production_countries.movie_id contains a value that cannot be normalized to an integer movie ID.',
            ],
        ];

        foreach ($checks as [$query, $message]) {
            if ($this->fetchRow($query) !== false) {
                throw new RuntimeException('Cannot normalize the SQLite schema: ' . $message);
            }
        }

    }

    private function backfillUserFlags() : void
    {
        $this->execute(
            <<<SQL
            UPDATE user SET
                display_imdb_rating = COALESCE(display_imdb_rating, 1),
                display_tmdb_rating = COALESCE(display_tmdb_rating, 1),
                emby_scrobble_views = COALESCE(emby_scrobble_views, 1),
                jellyfin_scrobble_views = COALESCE(jellyfin_scrobble_views, 1),
                kodi_scrobble_views = COALESCE(kodi_scrobble_views, 1),
                mastodon_enabled = COALESCE(mastodon_enabled, 0),
                mastodon_post_automatic = COALESCE(mastodon_post_automatic, 1),
                mastodon_post_visibility = COALESCE(mastodon_post_visibility, 'public'),
                plex_scrobble_ratings = COALESCE(plex_scrobble_ratings, 0),
                plex_scrobble_views = COALESCE(plex_scrobble_views, 1),
                watchlist_automatic_removal_enabled = COALESCE(watchlist_automatic_removal_enabled, 1)
            WHERE
                display_imdb_rating IS NULL
                OR display_tmdb_rating IS NULL
                OR emby_scrobble_views IS NULL
                OR jellyfin_scrobble_views IS NULL
                OR kodi_scrobble_views IS NULL
                OR mastodon_enabled IS NULL
                OR mastodon_post_automatic IS NULL
                OR mastodon_post_visibility IS NULL
                OR plex_scrobble_ratings IS NULL
                OR plex_scrobble_views IS NULL
                OR watchlist_automatic_removal_enabled IS NULL
            SQL,
        );
    }

    private function normalizeCacheTables() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE cache_tmdb_languages_normalized (
                iso_639_1 TEXT NOT NULL,
                english_name TEXT NOT NULL,
                PRIMARY KEY (iso_639_1)
            );
            INSERT INTO cache_tmdb_languages_normalized SELECT * FROM cache_tmdb_languages;
            DROP TABLE cache_tmdb_languages;
            ALTER TABLE cache_tmdb_languages_normalized RENAME TO cache_tmdb_languages;

            CREATE TABLE cache_trakt_user_movie_rating_normalized (
                trakt_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                rating INTEGER DEFAULT NULL,
                rated_at TEXT NOT NULL,
                PRIMARY KEY (trakt_id),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            );
            INSERT INTO cache_trakt_user_movie_rating_normalized SELECT * FROM cache_trakt_user_movie_rating;
            DROP TABLE cache_trakt_user_movie_rating;
            ALTER TABLE cache_trakt_user_movie_rating_normalized RENAME TO cache_trakt_user_movie_rating;

            CREATE TABLE cache_trakt_user_movie_watched_normalized (
                trakt_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                last_updated_at TEXT NOT NULL,
                PRIMARY KEY (trakt_id),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            );
            INSERT INTO cache_trakt_user_movie_watched_normalized SELECT * FROM cache_trakt_user_movie_watched;
            DROP TABLE cache_trakt_user_movie_watched;
            ALTER TABLE cache_trakt_user_movie_watched_normalized RENAME TO cache_trakt_user_movie_watched;
            SQL,
        );
    }

    private function normalizePersonTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE person_normalized (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                gender INTEGER NOT NULL CHECK (gender IN (0, 1, 2, 3)),
                known_for_department TEXT DEFAULT NULL,
                poster_path TEXT DEFAULT NULL,
                biography TEXT DEFAULT NULL,
                birth_date TEXT DEFAULT NULL,
                place_of_birth TEXT DEFAULT NULL,
                death_date TEXT DEFAULT NULL,
                tmdb_id INTEGER DEFAULT NULL,
                imdb_id TEXT DEFAULT NULL,
                tmdb_poster_path TEXT DEFAULT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT DEFAULT NULL,
                updated_at_tmdb TEXT DEFAULT NULL,
                UNIQUE (tmdb_id)
            );
            INSERT INTO person_normalized (
                id, name, gender, known_for_department, poster_path, biography, birth_date,
                place_of_birth, death_date, tmdb_id, imdb_id, tmdb_poster_path, created_at,
                updated_at, updated_at_tmdb
            )
            SELECT
                id, name, gender, known_for_department, poster_path, biography, birth_date,
                place_of_birth, death_date, tmdb_id, imdb_id, tmdb_poster_path, created_at,
                updated_at, updated_at_tmdb
            FROM person;
            DROP TABLE person;
            ALTER TABLE person_normalized RENAME TO person;
            SQL,
        );
    }

    private function normalizeUserTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE user_normalized (
                id INTEGER PRIMARY KEY,
                email TEXT NOT NULL,
                name TEXT NOT NULL,
                password TEXT NOT NULL,
                totp_uri TEXT DEFAULT NULL,
                is_admin INTEGER DEFAULT 0,
                dashboard_visible_rows TEXT DEFAULT NULL,
                dashboard_extended_rows TEXT DEFAULT NULL,
                dashboard_order_rows TEXT DEFAULT NULL,
                jellyfin_access_token TEXT DEFAULT NULL,
                jellyfin_user_id TEXT DEFAULT NULL,
                jellyfin_server_url TEXT DEFAULT NULL,
                jellyfin_sync_enabled INTEGER DEFAULT 0,
                privacy_level INTEGER DEFAULT 1,
                date_format_id INTEGER DEFAULT 0,
                trakt_user_name TEXT DEFAULT NULL,
                plex_webhook_uuid TEXT DEFAULT NULL,
                jellyfin_webhook_uuid TEXT DEFAULT NULL,
                emby_webhook_uuid TEXT DEFAULT NULL,
                kodi_webhook_uuid TEXT DEFAULT NULL,
                trakt_client_id TEXT DEFAULT NULL,
                plex_client_id TEXT DEFAULT NULL,
                plex_client_temporary_code TEXT DEFAULT NULL,
                plex_access_token TEXT DEFAULT NULL,
                plex_account_id TEXT DEFAULT NULL,
                plex_server_url TEXT DEFAULT NULL,
                jellyfin_scrobble_views INTEGER NOT NULL DEFAULT 1,
                emby_scrobble_views INTEGER NOT NULL DEFAULT 1,
                kodi_scrobble_views INTEGER NOT NULL DEFAULT 1,
                plex_scrobble_views INTEGER NOT NULL DEFAULT 1,
                plex_scrobble_ratings INTEGER NOT NULL DEFAULT 0,
                radarr_feed_uuid TEXT DEFAULT NULL,
                watchlist_automatic_removal_enabled INTEGER NOT NULL DEFAULT 1,
                country TEXT DEFAULT NULL,
                display_character_names INTEGER DEFAULT 1,
                locations_enabled INTEGER DEFAULT 1,
                core_account_changes_disabled INTEGER DEFAULT 0,
                display_tmdb_rating INTEGER NOT NULL DEFAULT 1,
                display_imdb_rating INTEGER NOT NULL DEFAULT 1,
                mastodon_enabled INTEGER NOT NULL DEFAULT 0,
                mastodon_username TEXT DEFAULT NULL,
                mastodon_access_token TEXT DEFAULT NULL,
                mastodon_post_visibility TEXT NOT NULL DEFAULT 'public'
                    CHECK (mastodon_post_visibility IN ('public', 'private', 'unlisted', 'direct')),
                mastodon_post_automatic INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                UNIQUE (email),
                UNIQUE (name)
            );
            INSERT INTO user_normalized (
                id, email, name, password, totp_uri, is_admin, dashboard_visible_rows,
                dashboard_extended_rows, dashboard_order_rows, jellyfin_access_token,
                jellyfin_user_id, jellyfin_server_url, jellyfin_sync_enabled, privacy_level,
                date_format_id, trakt_user_name, plex_webhook_uuid, jellyfin_webhook_uuid,
                emby_webhook_uuid, kodi_webhook_uuid, trakt_client_id, plex_client_id,
                plex_client_temporary_code, plex_access_token, plex_account_id, plex_server_url,
                jellyfin_scrobble_views, emby_scrobble_views, kodi_scrobble_views,
                plex_scrobble_views, plex_scrobble_ratings, radarr_feed_uuid,
                watchlist_automatic_removal_enabled, country, display_character_names,
                locations_enabled, core_account_changes_disabled, display_tmdb_rating,
                display_imdb_rating, mastodon_enabled, mastodon_username, mastodon_access_token,
                mastodon_post_visibility, mastodon_post_automatic, created_at
            )
            SELECT
                id, email, name, password, totp_uri, is_admin, dashboard_visible_rows,
                dashboard_extended_rows, dashboard_order_rows, jellyfin_access_token,
                jellyfin_user_id, jellyfin_server_url, jellyfin_sync_enabled, privacy_level,
                date_format_id, trakt_user_name, plex_webhook_uuid, jellyfin_webhook_uuid,
                emby_webhook_uuid, kodi_webhook_uuid, trakt_client_id, plex_client_id,
                plex_client_temporary_code, plex_access_token, plex_account_id, plex_server_url,
                jellyfin_scrobble_views, emby_scrobble_views, kodi_scrobble_views,
                plex_scrobble_views, plex_scrobble_ratings, radarr_feed_uuid,
                watchlist_automatic_removal_enabled, country, display_character_names,
                locations_enabled, core_account_changes_disabled, display_tmdb_rating,
                display_imdb_rating, mastodon_enabled, mastodon_username, mastodon_access_token,
                mastodon_post_visibility, mastodon_post_automatic, created_at
            FROM user;
            DROP TABLE user;
            ALTER TABLE user_normalized RENAME TO user;
            SQL,
        );
    }

    private function normalizeMovieTables() : void
    {
        $this->execute(
            <<<SQL
            CREATE UNIQUE INDEX unique_genre_tmdb_id ON genre(tmdb_id);

            CREATE TABLE location_normalized (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                is_cinema INTEGER DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT DEFAULT NULL,
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            );
            INSERT INTO location_normalized
            SELECT id, CAST(user_id AS INTEGER), name, is_cinema, created_at, updated_at FROM location;
            DROP TABLE location;
            ALTER TABLE location_normalized RENAME TO location;

            CREATE TABLE movie_production_countries_normalized (
                movie_id INTEGER NOT NULL,
                iso_3166_1 TEXT NOT NULL,
                position INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                PRIMARY KEY (movie_id, iso_3166_1),
                FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE,
                FOREIGN KEY (iso_3166_1) REFERENCES country(iso_3166_1) ON DELETE CASCADE
            );
            INSERT INTO movie_production_countries_normalized
            SELECT CAST(movie_id AS INTEGER), iso_3166_1, position, created_at FROM movie_production_countries;
            DROP TABLE movie_production_countries;
            ALTER TABLE movie_production_countries_normalized RENAME TO movie_production_countries;

            CREATE TABLE movie_user_rating_normalized (
                movie_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT DEFAULT NULL,
                PRIMARY KEY (movie_id, user_id),
                FOREIGN KEY (movie_id) REFERENCES movie(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            );
            INSERT INTO movie_user_rating_normalized SELECT * FROM movie_user_rating;
            DROP TABLE movie_user_rating;
            ALTER TABLE movie_user_rating_normalized RENAME TO movie_user_rating;

            CREATE TABLE movie_user_watch_dates_normalized (
                movie_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                watched_at TEXT DEFAULT NULL,
                plays INTEGER DEFAULT 1,
                comment TEXT DEFAULT NULL,
                position INTEGER NOT NULL DEFAULT 1,
                location_id INTEGER DEFAULT NULL,
                UNIQUE (movie_id, user_id, watched_at),
                FOREIGN KEY (movie_id) REFERENCES movie(id),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
                FOREIGN KEY (location_id) REFERENCES location(id) ON DELETE CASCADE
            );
            INSERT INTO movie_user_watch_dates_normalized SELECT * FROM movie_user_watch_dates;
            DROP TABLE movie_user_watch_dates;
            ALTER TABLE movie_user_watch_dates_normalized RENAME TO movie_user_watch_dates;
            SQL,
        );
    }

    private function normalizeAuthenticationTable() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE user_auth_token_normalized (
                id INTEGER PRIMARY KEY,
                user_id INTEGER NOT NULL,
                token CHAR(32) NOT NULL,
                device_name TEXT NOT NULL,
                user_agent TEXT NOT NULL,
                expiration_date TEXT NOT NULL,
                created_at TEXT NOT NULL,
                UNIQUE (token),
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            );
            INSERT INTO user_auth_token_normalized SELECT * FROM user_auth_token;
            DROP TABLE user_auth_token;
            ALTER TABLE user_auth_token_normalized RENAME TO user_auth_token;
            SQL,
        );
    }

    private function addIndexes() : void
    {
        $this->execute(
            <<<SQL
            CREATE INDEX index_cache_trakt_user_movie_rating_user_id
                ON cache_trakt_user_movie_rating(user_id);
            CREATE INDEX index_cache_trakt_user_movie_watched_user_id
                ON cache_trakt_user_movie_watched(user_id);
            CREATE INDEX index_job_queue_user_id ON job_queue(user_id);
            CREATE INDEX index_location_user_id ON location(user_id);
            CREATE INDEX index_movie_cast_person_id ON movie_cast(person_id);
            CREATE INDEX index_movie_crew_person_id ON movie_crew(person_id);
            CREATE INDEX index_movie_genre_genre_id ON movie_genre(genre_id);
            CREATE INDEX index_movie_production_company_company_id
                ON movie_production_company(company_id);
            CREATE INDEX index_movie_production_countries_iso_3166_1
                ON movie_production_countries(iso_3166_1);
            CREATE INDEX index_movie_user_rating_user_id ON movie_user_rating(user_id);
            CREATE INDEX index_movie_user_watch_dates_user_id ON movie_user_watch_dates(user_id);
            CREATE INDEX index_movie_user_watch_dates_location_id ON movie_user_watch_dates(location_id);
            CREATE INDEX index_user_auth_token_user_id ON user_auth_token(user_id);
            CREATE INDEX index_user_person_settings_person_id ON user_person_settings(person_id);
            CREATE INDEX index_watchlist_user_id ON watchlist(user_id);
            SQL,
        );
    }
}
