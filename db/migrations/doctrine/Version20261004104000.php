<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004104000 extends AbstractMigration
{
    private const string LOCATION_CONSTRAINT = 'fk_movie_user_watch_dates_location_id';

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

        $this->addSql('ALTER TABLE user_auth_token MODIFY token CHAR(64) NOT NULL');
        $this->addSql(
            'ALTER TABLE movie_user_watch_dates DROP FOREIGN KEY ' . self::LOCATION_CONSTRAINT,
        );
        $this->addSql(
            'ALTER TABLE movie_user_watch_dates '
            . 'ADD CONSTRAINT ' . self::LOCATION_CONSTRAINT . ' '
            . 'FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL',
        );

        if ($this->connection->createSchemaManager()->tablesExist(['user_login_attempt']) === false) {
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
        }
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException(
            'The corrected schema cannot be safely restored to its previous state.',
        );
    }
}
