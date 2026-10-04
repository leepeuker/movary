<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004104000 extends AbstractMigration
{
    private const string LOCATION_CONSTRAINT = 'fk_movie_user_watch_dates_location_id';
    private const string PERSON_GENDER_CONSTRAINT = 'chk_person_gender';
    private const string USER_MASTODON_VISIBILITY_CONSTRAINT = 'chk_user_mastodon_post_visibility';

    public function getDescription() : string
    {
        return 'Repair MySQL 8 migrations skipped by platform detection';
    }

    public function isTransactional() : bool
    {
        return false;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof AbstractMySQLPlatform === false) {
            return;
        }

        if ($this->hasAuthTokenColumnDefinition() === false) {
            $this->addSql('ALTER TABLE user_auth_token MODIFY token CHAR(64) NOT NULL');
        }
        $this->repairLocationForeignKey();
        if ($this->hasCheckConstraint('person', self::PERSON_GENDER_CONSTRAINT) === false) {
            $this->addSql(
                'ALTER TABLE person ADD CONSTRAINT ' . self::PERSON_GENDER_CONSTRAINT . ' '
                . 'CHECK (gender IN (0, 1, 2, 3))',
            );
        }
        if ($this->hasCheckConstraint('user', self::USER_MASTODON_VISIBILITY_CONSTRAINT) === false) {
            $this->addSql(
                'ALTER TABLE user ADD CONSTRAINT ' . self::USER_MASTODON_VISIBILITY_CONSTRAINT . ' '
                . "CHECK (mastodon_post_visibility IN ('public', 'private', 'unlisted', 'direct'))",
            );
        }

        $this->repairLoginAttemptTable();
    }

    private function hasAuthTokenColumnDefinition() : bool
    {
        $column = $this->connection->fetchAssociative(
            <<<'SQL'
            SELECT COLUMN_TYPE, IS_NULLABLE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_auth_token'
              AND COLUMN_NAME = 'token'
            SQL,
        );

        return $column !== false
            && strtolower((string)$column['COLUMN_TYPE']) === 'char(64)'
            && $column['IS_NULLABLE'] === 'NO';
    }

    private function repairLocationForeignKey() : void
    {
        $onDelete = $this->connection->fetchOne(
            <<<'SQL'
            SELECT DELETE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'movie_user_watch_dates'
              AND CONSTRAINT_NAME = ?
            SQL,
            [self::LOCATION_CONSTRAINT],
        );

        if ($onDelete === 'SET NULL') {
            return;
        }
        if ($onDelete !== false) {
            $this->addSql(
                'ALTER TABLE movie_user_watch_dates DROP FOREIGN KEY ' . self::LOCATION_CONSTRAINT,
            );
        }
        $this->addSql(
            'ALTER TABLE movie_user_watch_dates '
            . 'ADD CONSTRAINT ' . self::LOCATION_CONSTRAINT . ' '
            . 'FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL',
        );
    }

    private function repairLoginAttemptTable() : void
    {
        $schemaManager = $this->connection->createSchemaManager();

        if ($schemaManager->tablesExist(['user_login_attempt']) === false) {
            $this->addSql(
                'CREATE TABLE user_login_attempt ('
                . 'id INT AUTO_INCREMENT NOT NULL, '
                . 'subject_hash CHAR(64) NOT NULL, '
                . 'created_at DATETIME NOT NULL, '
                . 'PRIMARY KEY(id)'
                . ')',
            );
            $this->addSql(
                'CREATE INDEX index_user_login_attempt_subject '
                . 'ON user_login_attempt (subject_hash, created_at)',
            );
            $this->addSql('CREATE INDEX index_user_login_attempt_created_at ON user_login_attempt (created_at)');

            return;
        }

        $table = $schemaManager->introspectTable('user_login_attempt');
        if ($table->hasIndex('index_user_login_attempt_subject') === false) {
            $this->addSql(
                'CREATE INDEX index_user_login_attempt_subject '
                . 'ON user_login_attempt (subject_hash, created_at)',
            );
        }
        if ($table->hasIndex('index_user_login_attempt_created_at') === false) {
            $this->addSql('CREATE INDEX index_user_login_attempt_created_at ON user_login_attempt (created_at)');
        }
    }

    private function hasCheckConstraint(string $tableName, string $constraintName) : bool
    {
        return $this->connection->fetchOne(
            <<<'SQL'
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'CHECK'
            SQL,
            [$tableName, $constraintName],
        ) !== false;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException(
            'The corrected schema cannot be safely restored to its previous state.',
        );
    }
}
