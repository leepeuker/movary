<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261029000000 extends AbstractMigration
{
    private const int MAX_PLAYS = 1000000;

    private const string STAGING_TABLE = 'movie_user_watch_dates_play_staging';

    private const string BACKUP_TABLE = 'movie_user_watch_dates_aggregate_backup';

    public function getDescription() : string
    {
        return 'Expand aggregate watch history into individual plays';
    }

    public function isTransactional() : bool
    {
        return $this->platform instanceof SqlitePlatform;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof MySQLPlatform === false && $this->platform instanceof SqlitePlatform === false) {
            throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
        }

        $this->preflight();
        $this->createStagingTable();
        $this->expandRows();
        $this->validateStagingTable();
        $this->replaceTable();
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        throw new RuntimeException('Individual play history cannot be collapsed without losing per-play metadata. Restore a backup instead.');
    }

    private function preflight() : void
    {
        if (isset($this->connection->createSchemaManager()->listTableColumns('movie_user_watch_dates')['id'])) {
            throw new RuntimeException('History already has play IDs. Inspect migration metadata before retrying.');
        }

        $invalid = (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM movie_user_watch_dates WHERE plays IS NULL OR plays < 1 OR plays > 32767 OR position IS NULL OR position < 1 OR position > 32767',
        );
        if ($invalid !== 0) {
            throw new RuntimeException("Cannot expand $invalid history rows with invalid plays or positions.");
        }

        $total = (int)$this->connection->fetchOne('SELECT COALESCE(SUM(plays), 0) FROM movie_user_watch_dates');
        if ($total > self::MAX_PLAYS) {
            throw new RuntimeException('History expansion exceeds the 1,000,000-play limit.');
        }

        $pendingJobs = (int)$this->connection->fetchOne(
            "SELECT COUNT(*) FROM job_queue WHERE job_type = 'mastodon_post_play' AND job_status IN ('waiting', 'in progress')",
        );
        if ($pendingJobs !== 0) {
            throw new RuntimeException('Drain pending Mastodon play-posting jobs before migrating history.');
        }

        if ($this->tableExists(self::STAGING_TABLE) || $this->tableExists(self::BACKUP_TABLE)) {
            throw new RuntimeException('An earlier play-history migration attempt needs inspection before retrying.');
        }
    }

    private function createStagingTable() : void
    {
        if ($this->platform instanceof MySQLPlatform) {
            $this->connection->executeStatement(
                'CREATE TABLE ' . self::STAGING_TABLE . ' (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    movie_id INT UNSIGNED NOT NULL,
                    user_id INT UNSIGNED NOT NULL,
                    location_id INT UNSIGNED DEFAULT NULL,
                    watched_at DATE DEFAULT NULL,
                    plays SMALLINT DEFAULT 1 NOT NULL,
                    comment TEXT DEFAULT NULL,
                    position SMALLINT DEFAULT 1 NOT NULL,
                    PRIMARY KEY (id),
                    INDEX index_movie_user_watch_dates_user_id (user_id),
                    INDEX index_movie_user_watch_dates_location_id (location_id),
                    INDEX index_movie_user_watch_dates_movie_user_date (movie_id, user_id, watched_at),
                    CONSTRAINT play_staging_fk_movie FOREIGN KEY (movie_id) REFERENCES movie (id),
                    CONSTRAINT play_staging_fk_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
                    CONSTRAINT play_staging_fk_location FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB',
            );

            return;
        }

        $this->connection->executeStatement(
            'CREATE TABLE ' . self::STAGING_TABLE . ' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                movie_id INTEGER UNSIGNED NOT NULL,
                user_id INTEGER UNSIGNED NOT NULL,
                location_id INTEGER UNSIGNED DEFAULT NULL,
                watched_at TEXT DEFAULT NULL,
                plays SMALLINT DEFAULT 1 NOT NULL,
                comment CLOB DEFAULT NULL,
                position SMALLINT DEFAULT 1 NOT NULL,
                CONSTRAINT play_staging_fk_movie FOREIGN KEY (movie_id) REFERENCES movie (id),
                CONSTRAINT play_staging_fk_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
                CONSTRAINT play_staging_fk_location FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL
            )',
        );
    }

    private function expandRows() : void
    {
        $offset = 0;
        $batch = [];
        do {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT movie_id, user_id, watched_at, plays, comment, position, location_id
                 FROM movie_user_watch_dates
                 ORDER BY user_id, watched_at, position, movie_id, comment, location_id, plays
                 LIMIT 500 OFFSET ' . $offset,
            );
            foreach ($rows as $row) {
                for ($sequence = 0; $sequence < (int)$row['plays']; $sequence++) {
                    $batch[] = [
                        $row['movie_id'],
                        $row['user_id'],
                        $row['watched_at'],
                        $sequence === 0 ? $row['comment'] : null,
                        $row['position'],
                        $sequence === 0 ? $row['location_id'] : null,
                    ];
                    if (count($batch) === 100) {
                        $this->insertBatch($batch);
                        $batch = [];
                    }
                }
            }
            $offset += count($rows);
        } while (count($rows) === 500);

        if ($batch !== []) {
            $this->insertBatch($batch);
        }
    }

    private function insertBatch(array $batch) : void
    {
        $values = implode(', ', array_fill(0, count($batch), '(?, ?, ?, 1, ?, ?, ?)'));
        $parameters = [];
        foreach ($batch as $row) {
            array_push($parameters, ...$row);
        }

        $this->connection->executeStatement(
            'INSERT INTO ' . self::STAGING_TABLE . ' (movie_id, user_id, watched_at, plays, comment, position, location_id) VALUES ' . $values,
            $parameters,
        );
    }

    private function validateStagingTable() : void
    {
        $mismatch = $this->connection->fetchOne(
            'SELECT 1 FROM (
                SELECT user_id, movie_id, SUM(plays) AS total FROM movie_user_watch_dates GROUP BY user_id, movie_id
            ) source LEFT JOIN (
                SELECT user_id, movie_id, COUNT(*) AS total FROM ' . self::STAGING_TABLE . ' GROUP BY user_id, movie_id
            ) target ON source.user_id = target.user_id AND source.movie_id = target.movie_id
            WHERE target.total IS NULL OR source.total <> target.total LIMIT 1',
        );
        $sourceTotal = (int)$this->connection->fetchOne('SELECT COALESCE(SUM(plays), 0) FROM movie_user_watch_dates');
        $targetTotal = (int)$this->connection->fetchOne('SELECT COUNT(*) FROM ' . self::STAGING_TABLE);
        if ($mismatch !== false || $sourceTotal !== $targetTotal) {
            throw new RuntimeException('Expanded play counts do not match the source history.');
        }
    }

    private function replaceTable() : void
    {
        if ($this->platform instanceof MySQLPlatform) {
            $this->connection->executeStatement(
                'RENAME TABLE movie_user_watch_dates TO ' . self::BACKUP_TABLE . ', '
                . self::STAGING_TABLE . ' TO movie_user_watch_dates',
            );
        } else {
            $this->connection->executeStatement('ALTER TABLE movie_user_watch_dates RENAME TO ' . self::BACKUP_TABLE);
            $this->connection->executeStatement('ALTER TABLE ' . self::STAGING_TABLE . ' RENAME TO movie_user_watch_dates');
        }

        if ($this->platform instanceof SqlitePlatform) {
            $this->connection->executeStatement('DROP TABLE ' . self::BACKUP_TABLE);
            $this->connection->executeStatement('CREATE INDEX index_movie_user_watch_dates_user_id ON movie_user_watch_dates (user_id)');
            $this->connection->executeStatement('CREATE INDEX index_movie_user_watch_dates_location_id ON movie_user_watch_dates (location_id)');
            $this->connection->executeStatement('CREATE INDEX index_movie_user_watch_dates_movie_user_date ON movie_user_watch_dates (movie_id, user_id, watched_at)');
        } else {
            $this->connection->executeStatement('DROP TABLE ' . self::BACKUP_TABLE);
        }
    }

    private function tableExists(string $tableName) : bool
    {
        if ($this->platform instanceof MySQLPlatform) {
            return $this->connection->fetchOne(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$tableName],
            ) !== false;
        }

        return $this->connection->fetchOne("SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?", [$tableName]) !== false;
    }
}
