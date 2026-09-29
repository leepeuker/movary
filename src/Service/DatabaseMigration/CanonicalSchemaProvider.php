<?php declare(strict_types=1);

namespace Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Provider\SchemaProvider;

final class CanonicalSchemaProvider implements SchemaProvider
{
    public function createSchema() : Schema
    {
        $schema = new Schema();

        $this->createUserTable($schema);
        $this->createMovieTable($schema);
        $this->createPersonTable($schema);
        $this->createReferenceTables($schema);
        $this->createMovieRelationTables($schema);
        $this->createUserDataTables($schema);
        $this->createCacheTables($schema);
        $this->createInfrastructureTables($schema);

        return $schema;
    }

    private function createUserTable(Schema $schema) : void
    {
        $table = $schema->createTable('user');
        $this->addAutoIncrementId($table);
        $table->addColumn('email', Types::STRING, ['length' => 255]);
        $table->addColumn('name', Types::STRING, ['length' => 256]);
        $table->addColumn('password', Types::STRING, ['length' => 255]);
        $table->addColumn('totp_uri', Types::STRING, ['length' => 255, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('is_admin', Types::BOOLEAN, ['default' => false, 'notnull' => false]);
        $this->addText($table, 'dashboard_visible_rows', false);
        $this->addText($table, 'dashboard_extended_rows', false);
        $this->addText($table, 'dashboard_order_rows', false);
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
        $this->addText($table, 'mastodon_username', false);
        $this->addText($table, 'mastodon_access_token', false);
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

    private function createMovieTable(Schema $schema) : void
    {
        $table = $schema->createTable('movie');
        $this->addAutoIncrementId($table);
        $table->addColumn('title', Types::STRING, ['length' => 256]);
        $table->addColumn('trakt_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('imdb_id', Types::STRING, ['length' => 10, 'notnull' => false]);
        $table->addColumn('tmdb_id', Types::INTEGER, ['unsigned' => true]);
        $table->addColumn('letterboxd_id', Types::STRING, ['length' => 4, 'fixed' => true, 'notnull' => false]);
        $table->addColumn('poster_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $this->addText($table, 'tagline', false);
        $this->addText($table, 'overview', false);
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

    private function createPersonTable(Schema $schema) : void
    {
        $table = $schema->createTable('person');
        $this->addAutoIncrementId($table);
        $table->addColumn('name', Types::STRING, ['length' => 256]);
        $table->addColumn('gender', Types::SMALLINT, ['unsigned' => true]);
        $table->addColumn('known_for_department', Types::STRING, ['length' => 256, 'notnull' => false]);
        $table->addColumn('poster_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $this->addText($table, 'biography', false);
        $table->addColumn('birth_date', Types::DATE_MUTABLE, ['notnull' => false]);
        $table->addColumn('place_of_birth', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('death_date', Types::DATE_MUTABLE, ['notnull' => false]);
        $table->addColumn('tmdb_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $table->addColumn('imdb_id', Types::STRING, ['length' => 10, 'notnull' => false]);
        $table->addColumn('tmdb_poster_path', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addColumn('updated_at_tmdb', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $table->addUniqueIndex(['tmdb_id'], 'unique_person_tmdb_id');
    }

    private function createReferenceTables(Schema $schema) : void
    {
        $company = $schema->createTable('company');
        $this->addAutoIncrementId($company);
        $company->addColumn('name', Types::STRING, ['length' => 256]);
        $company->addColumn('origin_country', Types::STRING, ['length' => 2, 'fixed' => true, 'notnull' => false]);
        $company->addColumn('tmdb_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $this->addTimestamps($company);
        $company->addUniqueIndex(['tmdb_id'], 'unique_company_tmdb_id');

        $genre = $schema->createTable('genre');
        $this->addAutoIncrementId($genre);
        $genre->addColumn('name', Types::STRING, ['length' => 256]);
        $genre->addColumn('tmdb_id', Types::INTEGER, ['notnull' => false, 'unsigned' => true]);
        $this->addTimestamps($genre);
        $genre->addUniqueIndex(['name'], 'unique_genre_name');
        $genre->addUniqueIndex(['tmdb_id'], 'unique_genre_tmdb_id');

        $country = $schema->createTable('country');
        $country->addColumn('iso_3166_1', Types::STRING, ['length' => 2, 'fixed' => true]);
        $country->addColumn('english_name', Types::STRING, ['length' => 255]);
        $this->addTimestamps($country);
        $country->setPrimaryKey(['iso_3166_1']);
    }

    private function createMovieRelationTables(Schema $schema) : void
    {
        $cast = $schema->createTable('movie_cast');
        $this->addUnsignedInteger($cast, 'person_id');
        $this->addUnsignedInteger($cast, 'movie_id');
        $this->addText($cast, 'character_name', false);
        $cast->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $cast->addUniqueIndex(['movie_id', 'position'], 'unique_movie_cast_position');
        $cast->addIndex(['person_id'], 'index_movie_cast_person_id');
        $this->addCascadeForeignKey($cast, ['person_id'], 'person', ['id']);
        $this->addCascadeForeignKey($cast, ['movie_id'], 'movie', ['id']);

        $crew = $schema->createTable('movie_crew');
        $this->addUnsignedInteger($crew, 'person_id');
        $this->addUnsignedInteger($crew, 'movie_id');
        $crew->addColumn('job', Types::STRING, ['length' => 256]);
        $crew->addColumn('department', Types::STRING, ['length' => 256]);
        $crew->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $crew->addUniqueIndex(['movie_id', 'position'], 'unique_movie_crew_position');
        $crew->addIndex(['person_id'], 'index_movie_crew_person_id');
        $this->addCascadeForeignKey($crew, ['person_id'], 'person', ['id']);
        $this->addCascadeForeignKey($crew, ['movie_id'], 'movie', ['id']);

        $movieGenre = $schema->createTable('movie_genre');
        $this->addUnsignedInteger($movieGenre, 'genre_id');
        $this->addUnsignedInteger($movieGenre, 'movie_id');
        $movieGenre->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $movieGenre->addUniqueIndex(['genre_id', 'movie_id'], 'unique_movie_genre');
        $movieGenre->addUniqueIndex(['movie_id', 'position'], 'unique_movie_genre_position');
        $movieGenre->addIndex(['genre_id'], 'index_movie_genre_genre_id');
        $this->addCascadeForeignKey($movieGenre, ['genre_id'], 'genre', ['id']);
        $this->addCascadeForeignKey($movieGenre, ['movie_id'], 'movie', ['id']);

        $productionCompany = $schema->createTable('movie_production_company');
        $this->addUnsignedInteger($productionCompany, 'company_id');
        $this->addUnsignedInteger($productionCompany, 'movie_id');
        $productionCompany->addColumn('position', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $productionCompany->addUniqueIndex(['company_id', 'movie_id'], 'unique_movie_production_company');
        $productionCompany->addUniqueIndex(['movie_id', 'position'], 'unique_movie_production_company_position');
        $productionCompany->addIndex(['company_id'], 'index_movie_production_company_company_id');
        $this->addCascadeForeignKey($productionCompany, ['company_id'], 'company', ['id']);
        $this->addCascadeForeignKey($productionCompany, ['movie_id'], 'movie', ['id']);

        $productionCountry = $schema->createTable('movie_production_countries');
        $this->addUnsignedInteger($productionCountry, 'movie_id');
        $productionCountry->addColumn('iso_3166_1', Types::STRING, ['length' => 2, 'fixed' => true]);
        $productionCountry->addColumn('position', Types::SMALLINT);
        $productionCountry->addColumn('created_at', Types::DATETIME_MUTABLE);
        $productionCountry->setPrimaryKey(['movie_id', 'iso_3166_1']);
        $productionCountry->addIndex(['iso_3166_1'], 'index_movie_production_countries_iso_3166_1');
        $this->addCascadeForeignKey($productionCountry, ['movie_id'], 'movie', ['id']);
        $this->addCascadeForeignKey($productionCountry, ['iso_3166_1'], 'country', ['iso_3166_1']);
    }

    private function createUserDataTables(Schema $schema) : void
    {
        $rating = $schema->createTable('movie_user_rating');
        $this->addUnsignedInteger($rating, 'movie_id');
        $this->addUnsignedInteger($rating, 'user_id');
        $rating->addColumn('rating', Types::SMALLINT);
        $this->addTimestamps($rating);
        $rating->setPrimaryKey(['movie_id', 'user_id']);
        $rating->addIndex(['user_id'], 'index_movie_user_rating_user_id');
        $this->addCascadeForeignKey($rating, ['movie_id'], 'movie', ['id']);
        $this->addCascadeForeignKey($rating, ['user_id'], 'user', ['id']);

        $watchDates = $schema->createTable('movie_user_watch_dates');
        $this->addUnsignedInteger($watchDates, 'movie_id');
        $this->addUnsignedInteger($watchDates, 'user_id');
        $watchDates->addColumn('watched_at', Types::DATE_MUTABLE, ['notnull' => false]);
        $watchDates->addColumn('plays', Types::SMALLINT, ['default' => 1, 'notnull' => false]);
        $this->addText($watchDates, 'comment', false);
        $watchDates->addColumn('position', Types::SMALLINT, ['default' => 1]);
        $this->addUnsignedInteger($watchDates, 'location_id', false);
        $watchDates->addUniqueIndex(['movie_id', 'user_id', 'watched_at'], 'unique_movie_user_watch_date');
        $watchDates->addIndex(['user_id'], 'index_movie_user_watch_dates_user_id');
        $watchDates->addIndex(['location_id'], 'index_movie_user_watch_dates_location_id');
        $this->addForeignKey($watchDates, ['movie_id'], 'movie', ['id']);
        $this->addCascadeForeignKey($watchDates, ['user_id'], 'user', ['id']);
        $this->addCascadeForeignKey($watchDates, ['location_id'], 'location', ['id']);

        $watchlist = $schema->createTable('watchlist');
        $this->addUnsignedInteger($watchlist, 'movie_id', false);
        $this->addUnsignedInteger($watchlist, 'user_id', false);
        $watchlist->addColumn('added_at', Types::DATETIME_MUTABLE);
        $watchlist->addUniqueIndex(['movie_id', 'user_id'], 'unique_watchlist_movie_user');
        $watchlist->addIndex(['user_id'], 'index_watchlist_user_id');
        $this->addCascadeForeignKey($watchlist, ['movie_id'], 'movie', ['id']);
        $this->addCascadeForeignKey($watchlist, ['user_id'], 'user', ['id']);

        $location = $schema->createTable('location');
        $this->addAutoIncrementId($location);
        $this->addUnsignedInteger($location, 'user_id');
        $this->addText($location, 'name');
        $location->addColumn('is_cinema', Types::BOOLEAN, ['default' => false, 'notnull' => false]);
        $this->addTimestamps($location);
        $location->addIndex(['user_id'], 'index_location_user_id');
        $this->addCascadeForeignKey($location, ['user_id'], 'user', ['id']);

        $personSettings = $schema->createTable('user_person_settings');
        $this->addUnsignedInteger($personSettings, 'user_id');
        $this->addUnsignedInteger($personSettings, 'person_id');
        $personSettings->addColumn('is_hidden_in_top_lists', Types::BOOLEAN, [
            'default' => false,
            'notnull' => false,
        ]);
        $personSettings->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $personSettings->setPrimaryKey(['user_id', 'person_id']);
        $personSettings->addIndex(['person_id'], 'index_user_person_settings_person_id');
        $this->addCascadeForeignKey($personSettings, ['user_id'], 'user', ['id']);
        $this->addCascadeForeignKey($personSettings, ['person_id'], 'person', ['id']);
    }

    private function createCacheTables(Schema $schema) : void
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
        $this->addUnsignedInteger($rating, 'trakt_id');
        $this->addUnsignedInteger($rating, 'user_id');
        $rating->addColumn('rating', Types::SMALLINT, ['notnull' => false, 'unsigned' => true]);
        $rating->addColumn('rated_at', Types::DATETIME_MUTABLE);
        $rating->setPrimaryKey(['user_id', 'trakt_id']);
        $this->addCascadeForeignKey($rating, ['user_id'], 'user', ['id']);

        $watched = $schema->createTable('cache_trakt_user_movie_watched');
        $this->addUnsignedInteger($watched, 'trakt_id');
        $this->addUnsignedInteger($watched, 'user_id');
        $watched->addColumn('last_updated_at', Types::DATETIME_MUTABLE);
        $watched->setPrimaryKey(['user_id', 'trakt_id']);
        $this->addCascadeForeignKey($watched, ['user_id'], 'user', ['id']);

        $jellyfin = $schema->createTable('user_jellyfin_cache');
        $this->addUnsignedInteger($jellyfin, 'movary_user_id');
        $jellyfin->addColumn('jellyfin_item_id', Types::STRING, ['length' => 256]);
        $this->addUnsignedInteger($jellyfin, 'tmdb_id');
        $jellyfin->addColumn('watched', Types::BOOLEAN);
        $jellyfin->addColumn('last_watch_date', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $jellyfin->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $jellyfin->addColumn('created_at', Types::DATETIME_MUTABLE);
        $jellyfin->setPrimaryKey(['movary_user_id', 'jellyfin_item_id']);
        $this->addCascadeForeignKey($jellyfin, ['movary_user_id'], 'user', ['id']);
    }

    private function createInfrastructureTables(Schema $schema) : void
    {
        $jobQueue = $schema->createTable('job_queue');
        $this->addAutoIncrementId($jobQueue);
        $jobQueue->addColumn('job_type', Types::STRING, ['length' => 64]);
        $jobQueue->addColumn('job_status', Types::STRING, ['length' => 32]);
        $this->addUnsignedInteger($jobQueue, 'user_id', false);
        $this->addText($jobQueue, 'parameters', false);
        $this->addTimestamps($jobQueue);
        $jobQueue->addIndex(['job_status'], 'index_job_status');
        $jobQueue->addIndex(['job_type'], 'index_job_type');
        $jobQueue->addIndex(['user_id'], 'index_job_queue_user_id');
        $jobQueue->addForeignKeyConstraint('user', ['user_id'], ['id'], ['onDelete' => 'SET NULL']);

        $serverSetting = $schema->createTable('server_setting');
        $serverSetting->addColumn('key', Types::STRING, ['length' => 255]);
        $serverSetting->addColumn('value', Types::STRING, ['length' => 255, 'notnull' => false]);
        $serverSetting->addUniqueIndex(['key'], 'unique_server_setting_key');

        $apiToken = $schema->createTable('user_api_token');
        $this->addUnsignedInteger($apiToken, 'user_id');
        $apiToken->addColumn('token', Types::STRING, ['length' => 36, 'fixed' => true]);
        $apiToken->addColumn('created_at', Types::DATETIME_MUTABLE);
        $apiToken->setPrimaryKey(['token']);
        $apiToken->addUniqueIndex(['user_id'], 'unique_user_api_token_user_id');
        $this->addCascadeForeignKey($apiToken, ['user_id'], 'user', ['id']);

        $authToken = $schema->createTable('user_auth_token');
        $this->addAutoIncrementId($authToken);
        $this->addUnsignedInteger($authToken, 'user_id');
        $authToken->addColumn('token', Types::STRING, ['length' => 32, 'fixed' => true]);
        $authToken->addColumn('expiration_date', Types::DATETIME_MUTABLE);
        $authToken->addColumn('created_at', Types::DATETIME_MUTABLE);
        $authToken->addColumn('device_name', Types::STRING, ['length' => 256]);
        $this->addText($authToken, 'user_agent');
        $authToken->addUniqueIndex(['token'], 'unique_user_auth_token_token');
        $authToken->addIndex(['user_id'], 'index_user_auth_token_user_id');
        $this->addCascadeForeignKey($authToken, ['user_id'], 'user', ['id']);

        $resetToken = $schema->createTable('user_password_reset_token');
        $this->addUnsignedInteger($resetToken, 'user_id');
        $resetToken->addColumn('token_hash', Types::STRING, ['length' => 64, 'fixed' => true]);
        $resetToken->addColumn('expiration_date', Types::DATETIME_MUTABLE);
        $resetToken->addColumn('created_at', Types::DATETIME_MUTABLE);
        $resetToken->setPrimaryKey(['user_id']);
        $resetToken->addUniqueIndex(['token_hash'], 'unique_user_password_reset_token_hash');
        $this->addCascadeForeignKey($resetToken, ['user_id'], 'user', ['id']);
    }

    private function addAutoIncrementId(Table $table) : void
    {
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true, 'unsigned' => true]);
        $table->setPrimaryKey(['id']);
    }

    private function addUnsignedInteger(Table $table, string $name, bool $notnull = true) : void
    {
        $table->addColumn($name, Types::INTEGER, ['notnull' => $notnull, 'unsigned' => true]);
    }

    private function addTimestamps(Table $table) : void
    {
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, ['notnull' => false]);
    }

    private function addText(Table $table, string $name, bool $notnull = true) : void
    {
        $table->addColumn($name, Types::TEXT, ['length' => 65535, 'notnull' => $notnull]);
    }

    /**
     * @param list<string> $localColumns
     * @param list<string> $foreignColumns
     */
    private function addCascadeForeignKey(
        Table $table,
        array $localColumns,
        string $foreignTable,
        array $foreignColumns,
    ) : void {
        $this->addForeignKey($table, $localColumns, $foreignTable, $foreignColumns, 'CASCADE');
    }

    /**
     * @param list<string> $localColumns
     * @param list<string> $foreignColumns
     */
    private function addForeignKey(
        Table $table,
        array $localColumns,
        string $foreignTable,
        array $foreignColumns,
        ?string $onDelete = null,
    ) : void {
        $options = $onDelete === null ? [] : ['onDelete' => $onDelete];
        $table->addForeignKeyConstraint($foreignTable, $localColumns, $foreignColumns, $options);

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
