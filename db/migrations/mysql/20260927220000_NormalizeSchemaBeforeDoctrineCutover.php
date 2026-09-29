<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeSchemaBeforeDoctrineCutover extends AbstractMigration
{
    public function down() : void
    {
        throw new RuntimeException('The Doctrine cutover schema normalization is irreversible.');
    }

    public function up() : void
    {
        $this->assertExpectedSchema();
        $this->assertNoRows(
            "SELECT id FROM person WHERE gender NOT IN ('0', '1', '2', '3') LIMIT 1",
            'Cannot normalize person.gender: an unsupported value exists.',
        );
        $this->assertNoRows(
            "SELECT id FROM user WHERE mastodon_post_visibility NOT IN ('public', 'private', 'unlisted', 'direct') LIMIT 1",
            'Cannot normalize user.mastodon_post_visibility: an unsupported value exists.',
        );
        $this->assertNoRows(
            'SELECT id FROM user_auth_token WHERE CHAR_LENGTH(token) > 32 LIMIT 1',
            'Cannot normalize user_auth_token.token: a token longer than 32 characters exists.',
        );

        $this->execute(
            <<<SQL
            ALTER TABLE cache_trakt_user_movie_watched
                DROP INDEX uniqueTraktId,
                ADD PRIMARY KEY (trakt_id);

            ALTER TABLE job_queue
                DROP FOREIGN KEY job_queue_ibfk_1;
            ALTER TABLE job_queue
                ADD CONSTRAINT job_queue_ibfk_1
                    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL;

            ALTER TABLE movie_cast
                DROP FOREIGN KEY movie_cast_ibfk_1;
            ALTER TABLE movie_cast
                ADD CONSTRAINT movie_cast_ibfk_1
                    FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE CASCADE;

            ALTER TABLE movie_crew
                DROP FOREIGN KEY movie_crew_ibfk_1;
            ALTER TABLE movie_crew
                ADD CONSTRAINT movie_crew_ibfk_1
                    FOREIGN KEY (person_id) REFERENCES person(id) ON DELETE CASCADE;

            ALTER TABLE movie_genre
                DROP FOREIGN KEY movie_genre_ibfk_1;
            ALTER TABLE movie_genre
                ADD CONSTRAINT movie_genre_ibfk_1
                    FOREIGN KEY (genre_id) REFERENCES genre(id) ON DELETE CASCADE;

            ALTER TABLE country
                MODIFY created_at DATETIME NOT NULL;

            ALTER TABLE location
                MODIFY created_at DATETIME NOT NULL;

            ALTER TABLE movie_production_countries
                MODIFY created_at DATETIME NOT NULL;

            ALTER TABLE user_jellyfin_cache
                DROP FOREIGN KEY user_jellyfin_cache_ibfk_1;
            ALTER TABLE user_jellyfin_cache
                MODIFY movary_user_id INT UNSIGNED NOT NULL,
                MODIFY created_at DATETIME NOT NULL;
            ALTER TABLE user_jellyfin_cache
                ADD CONSTRAINT user_jellyfin_cache_ibfk_1
                    FOREIGN KEY (movary_user_id) REFERENCES user(id) ON DELETE CASCADE;

            ALTER TABLE person
                MODIFY gender TINYINT UNSIGNED NOT NULL,
                ADD CONSTRAINT chk_person_gender CHECK (gender IN (0, 1, 2, 3));

            ALTER TABLE user
                MODIFY mastodon_post_visibility VARCHAR(16) NOT NULL DEFAULT 'public',
                ADD CONSTRAINT chk_user_mastodon_post_visibility
                    CHECK (mastodon_post_visibility IN ('public', 'private', 'unlisted', 'direct'));

            ALTER TABLE user_auth_token
                MODIFY token CHAR(32) NOT NULL;
            SQL,
        );
    }

    private function assertExpectedSchema() : void
    {
        $checks = [
            [
                "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cache_trakt_user_movie_watched' AND INDEX_NAME = 'uniqueTraktId' AND COLUMN_NAME = 'trakt_id' AND NON_UNIQUE = 0",
                1,
                'the expected uniqueTraktId index is missing',
            ],
            [
                "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cache_trakt_user_movie_watched' AND INDEX_NAME = 'PRIMARY'",
                0,
                'cache_trakt_user_movie_watched already has a primary key',
            ],
            [
                "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME IN ('job_queue_ibfk_1', 'movie_cast_ibfk_1', 'movie_crew_ibfk_1', 'movie_genre_ibfk_1', 'user_jellyfin_cache_ibfk_1')",
                5,
                'one or more expected foreign keys are missing',
            ],
            [
                "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME IN ('chk_person_gender', 'chk_user_mastodon_post_visibility')",
                0,
                'one or more target check constraints already exist',
            ],
        ];

        foreach ($checks as [$query, $expectedCount, $message]) {
            $row = $this->fetchRow($query);
            $actualCount = $row === false ? 0 : (int)reset($row);
            if ($actualCount !== $expectedCount) {
                throw new RuntimeException(
                    'Cannot normalize the MySQL schema: ' . $message . '. Restore the database backup before retrying.',
                );
            }
        }
    }

    private function assertNoRows(string $query, string $message) : void
    {
        if ($this->fetchRow($query) !== false) {
            throw new RuntimeException($message);
        }
    }
}
