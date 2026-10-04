<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261003190000 extends AbstractMigration
{
    private const string LOCATION_CONSTRAINT = 'fk_movie_user_watch_dates_location_id';

    public function getDescription() : string
    {
        return 'Preserve watch dates when deleting a location';
    }

    public function isTransactional() : bool
    {
        return $this->platform instanceof SqlitePlatform;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        $this->replaceLocationForeignKey('SET NULL');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->replaceLocationForeignKey('CASCADE');
    }

    private function replaceLocationForeignKey(string $onDelete) : void {
        if ($this->platform instanceof AbstractMySQLPlatform) {
            $this->addSql(
                'ALTER TABLE movie_user_watch_dates DROP FOREIGN KEY ' . self::LOCATION_CONSTRAINT,
            );
            $this->addSql(
                'ALTER TABLE movie_user_watch_dates '
                . 'ADD CONSTRAINT ' . self::LOCATION_CONSTRAINT . ' '
                . "FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE $onDelete",
            );

            return;
        }

        if ($this->platform instanceof SqlitePlatform) {
            $this->rebuildSqliteWatchDatesTable($onDelete);

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    private function rebuildSqliteWatchDatesTable(string $onDelete) : void
    {
        $this->addSql(
            <<<SQL
            CREATE TABLE movie_user_watch_dates_new (
                movie_id INTEGER UNSIGNED NOT NULL,
                user_id INTEGER UNSIGNED NOT NULL,
                location_id INTEGER UNSIGNED DEFAULT NULL,
                watched_at TEXT DEFAULT NULL,
                plays SMALLINT DEFAULT 1,
                comment CLOB DEFAULT NULL,
                position SMALLINT DEFAULT 1 NOT NULL,
                CONSTRAINT movie_user_watch_dates_ibfk_1
                    FOREIGN KEY (movie_id) REFERENCES movie (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT movie_history_fk_user_id
                    FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT fk_movie_user_watch_dates_location_id
                    FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE $onDelete NOT DEFERRABLE INITIALLY IMMEDIATE
            )
            SQL,
        );
        $this->addSql(
            'INSERT INTO movie_user_watch_dates_new '
            . '(movie_id, user_id, location_id, watched_at, plays, comment, position) '
            . 'SELECT movie_id, user_id, location_id, watched_at, plays, comment, position '
            . 'FROM movie_user_watch_dates',
        );
        $this->addSql('DROP TABLE movie_user_watch_dates');
        $this->addSql('ALTER TABLE movie_user_watch_dates_new RENAME TO movie_user_watch_dates');
        $this->addSql(
            'CREATE UNIQUE INDEX unique_movie_user_watch_date '
            . 'ON movie_user_watch_dates (movie_id, user_id, watched_at)',
        );
        $this->addSql(
            'CREATE INDEX index_movie_user_watch_dates_user_id ON movie_user_watch_dates (user_id)',
        );
        $this->addSql(
            'CREATE INDEX index_movie_user_watch_dates_location_id ON movie_user_watch_dates (location_id)',
        );
    }
}
