<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928000000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create the canonical Movary schema at the Phinx cutover boundary';
    }

    public function isTransactional() : bool
    {
        return false;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        $targetSchema = self::createCutoverSchema(
            $this->connection->createSchemaManager()->createSchemaConfig(),
        );

        if ($this->platform instanceof MySQLPlatform) {
            self::applyMysqlPhysicalSchema($targetSchema);
        }

        if ($this->platform instanceof SqlitePlatform) {
            $targetSchema->getTable('person')->getColumn('gender')->setColumnDefinition(
                'SMALLINT NOT NULL CHECK (gender IN (0, 1, 2, 3))',
            );
            $targetSchema->getTable('user')->getColumn('mastodon_post_visibility')->setColumnDefinition(
                "VARCHAR(16) NOT NULL DEFAULT 'public' "
                . "CHECK (mastodon_post_visibility IN ('public', 'private', 'unlisted', 'direct'))",
            );
        }

        foreach ($targetSchema->toSql($this->platform) as $sql) {
            $this->addSql($sql);
        }

        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql(
                'ALTER TABLE person ADD CONSTRAINT chk_person_gender '
                . 'CHECK (gender IN (0, 1, 2, 3))',
            );
            $this->addSql(
                'ALTER TABLE user ADD CONSTRAINT chk_user_mastodon_post_visibility '
                . "CHECK (mastodon_post_visibility IN ('public', 'private', 'unlisted', 'direct'))",
            );
        }
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException(
            'The Movary baseline cannot be rolled back because doing so would delete all application data.',
        );
    }

    public static function createCutoverSchema(?SchemaConfig $schemaConfig = null) : Schema
    {
        $schema = new Schema([], [], $schemaConfig);

        self::createUserTable($schema);
        self::createMovieTable($schema);
        self::createPersonTable($schema);
        self::createReferenceTables($schema);
        self::createMovieRelationTables($schema);
        self::createUserDataTables($schema);
        self::createCacheTables($schema);
        self::createInfrastructureTables($schema);

        return $schema;
    }

    private static function createUserTable(Schema $schema) : void
    {
        $table = $schema->createTable('user');
        self::addAutoIncrementId($table);
        $table->addColumn('email', Types::STRING, ['length' => 255]);
        $table->addColumn('name', Types::STRING, ['length' => 256]);
        $table->addColumn('password', Types::STRING, ['length' => 255]);
        $table->addColumn('totp_uri', Types::STRING, ['length' => 255, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('is_admin', Types::BOOLEAN, ['default' => false, 'notnull' => false]);
        self::addText($table, 'dashboard_visible_rows', false);
        self::addText($table, 'dashboard_extended_rows', false);
        self::addText($table, 'dashboard_order_rows', false);
        $table->addColumn('jellyfin_access_token', Types::STRING, ['length' => 128, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('jellyfin_user_id', Types::STRING, ['length' => 128, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('jellyfin_server_url', Types::STRING, ['length' => 128, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('jellyfin_sync_enabled', Types::BOOLEAN, ['default' => false, 'notnull' => false]);
        $table->addColumn('jellyfin_webhook_uuid', Types::STRING, ['length' => 36, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('emby_webhook_uuid', Types::STRING, ['length' => 36, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('kodi_webhook_uuid', Types::STRING, ['length' => 36, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('jellyfin_scrobble_views', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('emby_scrobble_views', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('kodi_scrobble_views', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('privacy_level', Types::SMALLINT, ['default' => 1, 'notnull' => false, 'unsigned' => true]);
        $table->addColumn('date_format_id', Types::SMALLINT, ['default' => 0, 'notnull' => false, 'unsigned' => true]);
        $table->addColumn('plex_webhook_uuid', Types::STRING, ['length' => 36, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('trakt_user_name', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('trakt_client_id', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('plex_client_id', Types::STRING, ['length' => 64, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('plex_client_temporary_code', Types::STRING, ['length' => 64, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('plex_access_token', Types::STRING, ['length' => 128, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('plex_account_id', Types::STRING, ['length' => 64, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('plex_server_url', Types::STRING, ['length' => 128, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('plex_scrobble_views', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('plex_scrobble_ratings', Types::SMALLINT, ['default' => 0]);
        $table->addColumn('radarr_feed_uuid', Types::STRING, ['length' => 36, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('watchlist_automatic_removal_enabled', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('country', Types::STRING, ['length' => 2, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('display_character_names', Types::BOOLEAN, ['default' => true, 'notnull' => false]);
        $table->addColumn('locations_enabled', Types::BOOLEAN, ['default' => true, 'notnull' => false]);
        $table->addColumn('display_tmdb_rating', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('display_imdb_rating', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('mastodon_enabled', Types::SMALLINT, ['default' => 0]);
        self::addText($table, 'mastodon_username', false);
        self::addText($table, 'mastodon_access_token', false);
        $table->addColumn('mastodon_post_automatic', Types::SMALLINT, ['default' => 1]);
        $table->addColumn('mastodon_post_visibility', Types::STRING, ['length' => 16, 'default' => 'public']);
        $table->addColumn('core_account_changes_disabled', Types::SMALLINT, [
            'default' => 0,
            'notnull' => false,
            'unsigned' => true,
        ]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addUniqueIndex(['email'], 'unique_user_email');
        $table->addUniqueIndex(['name'], 'unique_user_name');
    }

    private static function createMovieTable(Schema $schema) : void
    {
        $table = $schema->createTable('movie');
        self::addAutoIncrementId($table);
        $table->addColumn('title', Types::STRING, ['length' => 256]);
        $table->addColumn('trakt_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('imdb_id', Types::STRING, ['length' => 10, 'notnull' => false]);
        $table->addColumn('tmdb_id', Types::INTEGER, ['unsigned' => true]);
        $table->addColumn('letterboxd_id', Types::STRING, ['length' => 4, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('poster_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        self::addText($table, 'tagline', false);
        self::addText($table, 'overview', false);
        $table->addColumn('original_language', Types::STRING, ['length' => 2, 'notnull' => false]);
        $table->addColumn('runtime', Types::SMALLINT, ['notnull' => false]);
        $table->addColumn('release_date', Types::DATE_MUTABLE, ['notnull' => false]);
        $table->addColumn('tmdb_vote_average', Types::DECIMAL, [
            'precision' => 3,
            'scale' => 1,
            'notnull' => false,
        ]);
        $table->addColumn('tmdb_vote_count', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('tmdb_poster_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('tmdb_backdrop_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('imdb_rating_average', Types::FLOAT, [
            'precision' => 3,
            'scale' => 1,
            'notnull' => false,
        ]);
        $table->addColumn('imdb_rating_vote_count', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('updated_at_tmdb', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('updated_at_imdb', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addUniqueIndex(['tmdb_id'], 'unique_movie_tmdb_id');
        $table->addUniqueIndex(['trakt_id'], 'unique_movie_trakt_id');
        $table->addUniqueIndex(['imdb_id'], 'unique_movie_imdb_id');
    }

    private static function createPersonTable(Schema $schema) : void
    {
        $table = $schema->createTable('person');
        self::addAutoIncrementId($table);
        $table->addColumn('name', Types::STRING, ['length' => 256]);
        $table->addColumn('gender', Types::SMALLINT, ['unsigned' => true]);
        $table->addColumn('known_for_department', Types::STRING, ['length' => 256, 'notnull' => false]);
        $table->addColumn('poster_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('birth_date', Types::DATE_MUTABLE, ['notnull' => false]);
        self::addText($table, 'biography', false);
        $table->addColumn('place_of_birth', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('death_date', Types::DATE_MUTABLE, ['notnull' => false]);
        $table->addColumn('tmdb_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('imdb_id', Types::STRING, ['length' => 10, 'notnull' => false]);
        $table->addColumn('tmdb_poster_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('updated_at_tmdb', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addUniqueIndex(['tmdb_id'], 'unique_person_tmdb_id');
    }

    private static function createReferenceTables(Schema $schema) : void
    {
        $company = $schema->createTable('company');
        self::addAutoIncrementId($company);
        $company->addColumn('name', Types::STRING, ['length' => 256]);
        $company->addColumn('origin_country', Types::STRING, ['length' => 2, 'fixed' => true, 'notnull' => false]);
        $company->addColumn('tmdb_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        self::addTimestamps($company);
        $company->addUniqueIndex(['tmdb_id'], 'unique_company_tmdb_id');

        $genre = $schema->createTable('genre');
        self::addAutoIncrementId($genre);
        $genre->addColumn('name', Types::STRING, ['length' => 256]);
        $genre->addColumn('tmdb_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        self::addTimestamps($genre);
        $genre->addUniqueIndex(['name'], 'unique_genre_name');
        $genre->addUniqueIndex(['tmdb_id'], 'unique_genre_tmdb_id');

        $country = $schema->createTable('country');
        $country->addColumn('iso_3166_1', Types::STRING, ['length' => 2, 'fixed' => true]);
        $country->addColumn('english_name', Types::STRING, ['length' => 255]);
        $country->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $country->addColumn('created_at', Types::DATETIME_MUTABLE);
        $country->setPrimaryKey(['iso_3166_1']);
    }

    private static function createMovieRelationTables(Schema $schema) : void
    {
        $cast = $schema->createTable('movie_cast');
        self::addUnsignedInteger($cast, 'person_id');
        self::addUnsignedInteger($cast, 'movie_id');
        self::addText($cast, 'character_name', false);
        $cast->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $cast->addUniqueIndex(['movie_id', 'position'], 'unique_movie_cast_position');
        $cast->addIndex(['person_id'], 'index_movie_cast_person_id');
        self::addCascadeForeignKey($cast, ['person_id'], 'person', ['id'], 'movie_cast_ibfk_1');
        self::addCascadeForeignKey($cast, ['movie_id'], 'movie', ['id'], 'movie_cast_ibfk_2');

        $crew = $schema->createTable('movie_crew');
        self::addUnsignedInteger($crew, 'person_id');
        self::addUnsignedInteger($crew, 'movie_id');
        $crew->addColumn('job', Types::STRING, ['length' => 256]);
        $crew->addColumn('department', Types::STRING, ['length' => 256]);
        $crew->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $crew->addUniqueIndex(['movie_id', 'position'], 'unique_movie_crew_position');
        $crew->addIndex(['person_id'], 'index_movie_crew_person_id');
        self::addCascadeForeignKey($crew, ['person_id'], 'person', ['id'], 'movie_crew_ibfk_1');
        self::addCascadeForeignKey($crew, ['movie_id'], 'movie', ['id'], 'movie_crew_ibfk_2');

        $movieGenre = $schema->createTable('movie_genre');
        self::addUnsignedInteger($movieGenre, 'genre_id');
        self::addUnsignedInteger($movieGenre, 'movie_id');
        $movieGenre->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $movieGenre->addUniqueIndex(['genre_id', 'movie_id'], 'unique_movie_genre');
        $movieGenre->addUniqueIndex(['movie_id', 'position'], 'unique_movie_genre_position');
        $movieGenre->addIndex(['genre_id'], 'index_movie_genre_genre_id');
        self::addCascadeForeignKey($movieGenre, ['genre_id'], 'genre', ['id'], 'movie_genre_ibfk_1');
        self::addCascadeForeignKey($movieGenre, ['movie_id'], 'movie', ['id'], 'movie_genre_ibfk_2');

        $productionCompany = $schema->createTable('movie_production_company');
        self::addUnsignedInteger($productionCompany, 'company_id');
        self::addUnsignedInteger($productionCompany, 'movie_id');
        $productionCompany->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $productionCompany->addUniqueIndex(['company_id', 'movie_id'], 'unique_movie_production_company');
        $productionCompany->addUniqueIndex(['movie_id', 'position'], 'unique_movie_production_company_position');
        $productionCompany->addIndex(['company_id'], 'index_movie_production_company_company_id');
        self::addCascadeForeignKey($productionCompany, ['company_id'], 'company', ['id'], 'movie_production_company_ibfk_1');
        self::addCascadeForeignKey($productionCompany, ['movie_id'], 'movie', ['id'], 'movie_production_company_ibfk_2');

        $productionCountry = $schema->createTable('movie_production_countries');
        self::addUnsignedInteger($productionCountry, 'movie_id');
        $productionCountry->addColumn('iso_3166_1', Types::STRING, ['length' => 2, 'fixed' => true]);
        $productionCountry->addColumn('position', Types::SMALLINT);
        $productionCountry->addColumn('created_at', Types::DATETIME_MUTABLE);
        $productionCountry->setPrimaryKey(['movie_id', 'iso_3166_1']);
        $productionCountry->addIndex(['iso_3166_1'], 'index_movie_production_countries_iso_3166_1');
        self::addCascadeForeignKey($productionCountry, ['movie_id'], 'movie', ['id'], 'movie_production_countries_ibfk_1');
        self::addCascadeForeignKey($productionCountry, ['iso_3166_1'], 'country', ['iso_3166_1'], 'movie_production_countries_ibfk_2');
    }

    private static function createUserDataTables(Schema $schema) : void
    {
        $rating = $schema->createTable('movie_user_rating');
        self::addUnsignedInteger($rating, 'movie_id');
        self::addUnsignedInteger($rating, 'user_id');
        $rating->addColumn('rating', Types::SMALLINT);
        $rating->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $rating->addColumn('created_at', Types::DATETIME_MUTABLE);
        $rating->setPrimaryKey(['movie_id', 'user_id']);
        $rating->addIndex(['user_id'], 'index_movie_user_rating_user_id');
        self::addCascadeForeignKey($rating, ['movie_id'], 'movie', ['id'], 'movie_user_rating_ibfk_1');
        self::addCascadeForeignKey($rating, ['user_id'], 'user', ['id'], 'movie_user_rating_ibfk_2');

        $watchDates = $schema->createTable('movie_user_watch_dates');
        self::addUnsignedInteger($watchDates, 'movie_id');
        self::addUnsignedInteger($watchDates, 'user_id');
        $watchDates->addColumn('watched_at', Types::DATE_MUTABLE, ['notnull' => false]);
        $watchDates->addColumn('plays', Types::SMALLINT, ['default' => 1, 'notnull' => false]);
        self::addText($watchDates, 'comment', false);
        $watchDates->addColumn('position', Types::SMALLINT, ['default' => 1]);
        self::addUnsignedInteger($watchDates, 'location_id', false);
        $watchDates->addUniqueIndex(['movie_id', 'user_id', 'watched_at'], 'unique_movie_user_watch_date');
        $watchDates->addIndex(['user_id'], 'index_movie_user_watch_dates_user_id');
        $watchDates->addIndex(['location_id'], 'index_movie_user_watch_dates_location_id');
        self::addForeignKey($watchDates, ['movie_id'], 'movie', ['id'], 'movie_user_watch_dates_ibfk_1');
        self::addCascadeForeignKey($watchDates, ['user_id'], 'user', ['id'], 'movie_history_fk_user_id');
        self::addCascadeForeignKey($watchDates, ['location_id'], 'location', ['id'], 'fk_movie_user_watch_dates_location_id');

        $watchlist = $schema->createTable('watchlist');
        self::addUnsignedInteger($watchlist, 'movie_id', false);
        self::addUnsignedInteger($watchlist, 'user_id', false);
        $watchlist->addColumn('added_at', Types::DATETIME_MUTABLE);
        $watchlist->addUniqueIndex(['movie_id', 'user_id'], 'unique_watchlist_movie_user');
        $watchlist->addIndex(['user_id'], 'index_watchlist_user_id');
        self::addCascadeForeignKey($watchlist, ['movie_id'], 'movie', ['id'], 'watchlist_ibfk_1');
        self::addCascadeForeignKey($watchlist, ['user_id'], 'user', ['id'], 'watchlist_ibfk_2');

        $location = $schema->createTable('location');
        self::addAutoIncrementId($location);
        self::addUnsignedInteger($location, 'user_id');
        self::addText($location, 'name');
        $location->addColumn('is_cinema', Types::BOOLEAN, ['default' => false, 'notnull' => false]);
        self::addTimestamps($location);
        $location->addIndex(['user_id'], 'index_location_user_id');
        self::addCascadeForeignKey($location, ['user_id'], 'user', ['id'], 'location_ibfk_1');

        $personSettings = $schema->createTable('user_person_settings');
        self::addUnsignedInteger($personSettings, 'user_id');
        self::addUnsignedInteger($personSettings, 'person_id');
        $personSettings->addColumn('is_hidden_in_top_lists', Types::BOOLEAN, [
            'default' => false,
            'notnull' => false,
        ]);
        $personSettings->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $personSettings->setPrimaryKey(['user_id', 'person_id']);
        $personSettings->addIndex(['person_id'], 'index_user_person_settings_person_id');
        self::addCascadeForeignKey($personSettings, ['user_id'], 'user', ['id'], 'user_person_settings_ibfk_1');
        self::addCascadeForeignKey($personSettings, ['person_id'], 'person', ['id'], 'user_person_settings_ibfk_2');
    }

    private static function createCacheTables(Schema $schema) : void
    {
        $letterboxd = $schema->createTable('cache_letterboxd_diary');
        $letterboxd->addColumn('diary_id', Types::STRING, ['length' => 255]);
        $letterboxd->addColumn('letterboxd_id', Types::STRING, ['length' => 4]);
        $letterboxd->setPrimaryKey(['diary_id']);

        $languages = $schema->createTable('cache_tmdb_languages');
        $languages->addColumn('iso_639_1', Types::STRING, ['length' => 2, 'fixed' => true]);
        $languages->addColumn('english_name', Types::STRING, ['length' => 256]);
        $languages->setPrimaryKey(['iso_639_1']);

        $rating = $schema->createTable('cache_trakt_user_movie_rating');
        self::addUnsignedInteger($rating, 'trakt_id');
        self::addUnsignedInteger($rating, 'user_id');
        $rating->addColumn('rating', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $rating->addColumn('rated_at', Types::DATETIME_MUTABLE);
        $rating->setPrimaryKey(['user_id', 'trakt_id']);
        self::addCascadeForeignKey($rating, ['user_id'], 'user', ['id'], 'cache_trakt_user_movie_rating_fk_user_id');

        $watched = $schema->createTable('cache_trakt_user_movie_watched');
        self::addUnsignedInteger($watched, 'trakt_id');
        self::addUnsignedInteger($watched, 'user_id');
        $watched->addColumn('last_updated_at', Types::DATETIME_MUTABLE);
        $watched->setPrimaryKey(['user_id', 'trakt_id']);
        self::addCascadeForeignKey($watched, ['user_id'], 'user', ['id'], 'cache_trakt_user_movie_watched_fk_user_id');

        $jellyfin = $schema->createTable('user_jellyfin_cache');
        self::addUnsignedInteger($jellyfin, 'movary_user_id');
        $jellyfin->addColumn('jellyfin_item_id', Types::STRING, ['length' => 256]);
        self::addUnsignedInteger($jellyfin, 'tmdb_id');
        $jellyfin->addColumn('watched', Types::BOOLEAN);
        $jellyfin->addColumn('last_watch_date', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $jellyfin->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $jellyfin->addColumn('created_at', Types::DATETIME_MUTABLE);
        $jellyfin->setPrimaryKey(['movary_user_id', 'jellyfin_item_id']);
        self::addCascadeForeignKey($jellyfin, ['movary_user_id'], 'user', ['id'], 'user_jellyfin_cache_ibfk_1');
    }

    private static function createInfrastructureTables(Schema $schema) : void
    {
        $jobQueue = $schema->createTable('job_queue');
        self::addAutoIncrementId($jobQueue);
        $jobQueue->addColumn('job_type', Types::STRING, ['length' => 64]);
        $jobQueue->addColumn('job_status', Types::STRING, ['length' => 32]);
        self::addUnsignedInteger($jobQueue, 'user_id', false);
        self::addText($jobQueue, 'parameters', false);
        $jobQueue->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $jobQueue->addColumn('created_at', Types::DATETIME_MUTABLE);
        $jobQueue->addIndex(['job_status'], 'index_job_status');
        $jobQueue->addIndex(['job_type'], 'index_job_type');
        $jobQueue->addIndex(['user_id'], 'index_job_queue_user_id');
        $jobQueue->addForeignKeyConstraint(
            'user',
            ['user_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'job_queue_ibfk_1',
        );

        $serverSetting = $schema->createTable('server_setting');
        $serverSetting->addColumn('key', Types::STRING, ['length' => 255]);
        $serverSetting->addColumn('value', Types::STRING, ['length' => 255, 'notnull' => false]);
        $serverSetting->addUniqueIndex(['key'], 'unique_server_setting_key');

        $apiToken = $schema->createTable('user_api_token');
        self::addUnsignedInteger($apiToken, 'user_id');
        $apiToken->addColumn('token', Types::STRING, ['length' => 36, 'fixed' => true]);
        $apiToken->addColumn('created_at', Types::DATETIME_MUTABLE);
        $apiToken->setPrimaryKey(['token']);
        $apiToken->addUniqueIndex(['user_id'], 'unique_user_api_token_user_id');
        self::addCascadeForeignKey($apiToken, ['user_id'], 'user', ['id'], 'user_api_token_ibfk_1');

        $authToken = $schema->createTable('user_auth_token');
        self::addAutoIncrementId($authToken);
        self::addUnsignedInteger($authToken, 'user_id');
        $authToken->addColumn('token', Types::STRING, ['length' => 32, 'fixed' => true]);
        $authToken->addColumn('expiration_date', Types::DATETIME_MUTABLE);
        $authToken->addColumn('created_at', Types::DATETIME_MUTABLE);
        $authToken->addColumn('device_name', Types::STRING, ['length' => 256]);
        self::addText($authToken, 'user_agent');
        $authToken->addUniqueIndex(['token'], 'unique_user_auth_token_token');
        $authToken->addIndex(['user_id'], 'index_user_auth_token_user_id');
        self::addCascadeForeignKey($authToken, ['user_id'], 'user', ['id'], 'user_auth_token_fk_user_id');

        $resetToken = $schema->createTable('user_password_reset_token');
        self::addUnsignedInteger($resetToken, 'user_id');
        $resetToken->addColumn('token_hash', Types::STRING, ['length' => 64, 'fixed' => true]);
        $resetToken->addColumn('expiration_date', Types::DATETIME_MUTABLE);
        $resetToken->addColumn('created_at', Types::DATETIME_MUTABLE);
        $resetToken->setPrimaryKey(['user_id']);
        $resetToken->addUniqueIndex(['token_hash'], 'unique_user_password_reset_token_hash');
        self::addCascadeForeignKey($resetToken, ['user_id'], 'user', ['id'], 'user_password_reset_token_ibfk_1');
    }

    private static function applyMysqlPhysicalSchema(Schema $schema) : void
    {
        $schema->getTable('movie')->getColumn('imdb_rating_average')->setColumnDefinition(
            'DOUBLE(3,1) DEFAULT NULL',
        );

        $indexNames = [
            'company' => ['unique_company_tmdb_id' => 'tmdb_id'],
            'genre' => [
                'unique_genre_name' => 'name',
                'unique_genre_tmdb_id' => 'tmdb_id',
            ],
            'job_queue' => ['index_job_queue_user_id' => 'job_queue_ibfk_1'],
            'location' => ['index_location_user_id' => 'user_id'],
            'movie' => [
                'unique_movie_imdb_id' => 'imdb_id',
                'unique_movie_tmdb_id' => 'tmdb_id',
                'unique_movie_trakt_id' => 'trakt_id',
            ],
            'movie_cast' => [
                'index_movie_cast_person_id' => 'movie_cast_ibfk_1',
                'unique_movie_cast_position' => 'movie_id',
            ],
            'movie_crew' => [
                'index_movie_crew_person_id' => 'movie_crew_ibfk_1',
                'unique_movie_crew_position' => 'movie_id',
            ],
            'movie_genre' => [
                'unique_movie_genre' => 'genre_id',
                'unique_movie_genre_position' => 'movie_id',
            ],
            'movie_production_company' => [
                'unique_movie_production_company' => 'company_id',
                'unique_movie_production_company_position' => 'movie_id',
            ],
            'movie_production_countries' => [
                'index_movie_production_countries_iso_3166_1' => 'iso_3166_1',
            ],
            'movie_user_rating' => ['index_movie_user_rating_user_id' => 'user_id'],
            'movie_user_watch_dates' => [
                'index_movie_user_watch_dates_location_id' => 'fk_movie_user_watch_dates_location_id',
                'index_movie_user_watch_dates_user_id' => 'movie_history_fk_user_id',
                'unique_movie_user_watch_date' => 'unique_watched_dates',
            ],
            'person' => ['unique_person_tmdb_id' => 'tmdb_id'],
            'server_setting' => ['unique_server_setting_key' => 'key'],
            'user' => [
                'unique_user_email' => 'email',
                'unique_user_name' => 'name',
            ],
            'user_api_token' => ['unique_user_api_token_user_id' => 'user_id'],
            'user_auth_token' => [
                'index_user_auth_token_user_id' => 'user_auth_token_fk_user_id',
                'unique_user_auth_token_token' => 'token',
            ],
            'user_password_reset_token' => ['unique_user_password_reset_token_hash' => 'token_hash'],
            'user_person_settings' => ['index_user_person_settings_person_id' => 'person_id'],
            'watchlist' => [
                'index_watchlist_user_id' => 'user_id',
                'unique_watchlist_movie_user' => 'movie_id',
            ],
        ];

        foreach ($indexNames as $tableName => $renames) {
            $table = $schema->getTable($tableName);
            foreach ($renames as $currentName => $mainName) {
                $table->renameIndex($currentName, $mainName);
            }
        }
    }

    private static function addAutoIncrementId(Table $table) : void
    {
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
        $table->setPrimaryKey(['id']);
    }

    private static function addUnsignedInteger(Table $table, string $name, bool $notnull = true) : void
    {
        $table->addColumn($name, Types::INTEGER, ['notnull' => $notnull, 'unsigned' => true]);
    }

    private static function addTimestamps(Table $table) : void
    {
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
    }

    private static function addText(Table $table, string $name, bool $notnull = true) : void
    {
        $table->addColumn($name, Types::TEXT, ['length' => 65535, 'notnull' => $notnull]);
    }

    /**
     * @param list<string> $localColumns
     * @param list<string> $foreignColumns
     */
    private static function addCascadeForeignKey(
        Table $table,
        array $localColumns,
        string $foreignTable,
        array $foreignColumns,
        string $name,
    ) : void {
        self::addForeignKey($table, $localColumns, $foreignTable, $foreignColumns, $name, 'CASCADE');
    }

    /**
     * @param list<string> $localColumns
     * @param list<string> $foreignColumns
     */
    private static function addForeignKey(
        Table $table,
        array $localColumns,
        string $foreignTable,
        array $foreignColumns,
        string $name,
        ?string $onDelete = null,
    ) : void {
        $options = $onDelete === null ? [] : ['onDelete' => $onDelete];
        $table->addForeignKeyConstraint($foreignTable, $localColumns, $foreignColumns, $options, $name);

        $exactIndexName = null;
        $coveredByAnotherIndex = false;
        foreach ($table->getIndexes() as $index) {
            $indexColumns = $index->getColumns();

            if ($index->isUnique() === false && $indexColumns === $localColumns) {
                $exactIndexName = $index->getName();
                continue;
            }

            if (array_slice($indexColumns, 0, count($localColumns)) === $localColumns) {
                $coveredByAnotherIndex = true;
            }
        }

        if ($exactIndexName !== null && $coveredByAnotherIndex === true) {
            $table->dropIndex($exactIndexName);
        }
    }
}
