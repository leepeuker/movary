<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class StreamlineSchemaBeforeDoctrineCutover extends AbstractMigration
{
    public function down() : void
    {
        throw new RuntimeException('The Doctrine cutover schema streamlining is irreversible.');
    }

    public function init() : void
    {
        $this->execute('PRAGMA foreign_keys = OFF');
    }

    public function up() : void
    {
        $this->assertForeignKeysValid('before streamlining');

        $this->execute(
            <<<SQL
            CREATE TABLE movie_genre_streamlined (
                genre_id INTEGER UNSIGNED NOT NULL,
                movie_id INTEGER UNSIGNED NOT NULL,
                position SMALLINT UNSIGNED DEFAULT NULL,
                CONSTRAINT movie_genre_ibfk_1
                    FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE,
                CONSTRAINT movie_genre_ibfk_2
                    FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE
            );
            INSERT INTO movie_genre_streamlined (genre_id, movie_id, position)
                SELECT genre_id, movie_id, position FROM movie_genre;
            DROP TABLE movie_genre;
            ALTER TABLE movie_genre_streamlined RENAME TO movie_genre;
            CREATE UNIQUE INDEX unique_movie_genre ON movie_genre (genre_id, movie_id);
            CREATE UNIQUE INDEX unique_movie_genre_position ON movie_genre (movie_id, position);

            CREATE TABLE movie_production_company_streamlined (
                company_id INTEGER UNSIGNED NOT NULL,
                movie_id INTEGER UNSIGNED NOT NULL,
                position SMALLINT UNSIGNED DEFAULT NULL,
                CONSTRAINT movie_production_company_ibfk_1
                    FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE,
                CONSTRAINT movie_production_company_ibfk_2
                    FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE
            );
            INSERT INTO movie_production_company_streamlined (company_id, movie_id, position)
                SELECT company_id, movie_id, position FROM movie_production_company;
            DROP TABLE movie_production_company;
            ALTER TABLE movie_production_company_streamlined RENAME TO movie_production_company;
            CREATE UNIQUE INDEX unique_movie_production_company
                ON movie_production_company (company_id, movie_id);
            CREATE UNIQUE INDEX unique_movie_production_company_position
                ON movie_production_company (movie_id, position);
            SQL,
        );

        $this->assertForeignKeysValid('after streamlining');
    }

    private function assertForeignKeysValid(string $phase) : void
    {
        $violation = $this->fetchRow('PRAGMA foreign_key_check');
        if ($violation === false) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Cannot streamline the SQLite schema: foreign-key violation %s in table %s at row %s.',
                $phase,
                $violation['table'],
                $violation['rowid'] ?? 'unknown',
            ),
        );
    }
}
