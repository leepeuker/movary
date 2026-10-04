<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004100000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Store failed login attempts for rate limiting';
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql(
                'CREATE TABLE user_login_attempt ('
                . 'id INT AUTO_INCREMENT NOT NULL, '
                . 'subject_hash CHAR(64) NOT NULL, '
                . 'created_at DATETIME NOT NULL, '
                . 'PRIMARY KEY(id)'
                . ')',
            );
        } else {
            $this->addSql(
                'CREATE TABLE user_login_attempt ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, '
                . 'subject_hash CHAR(64) NOT NULL, '
                . 'created_at DATETIME NOT NULL'
                . ')',
            );
        }
        $this->addSql(
            'CREATE INDEX index_user_login_attempt_subject '
            . 'ON user_login_attempt (subject_hash, created_at)',
        );
        $this->addSql('CREATE INDEX index_user_login_attempt_created_at ON user_login_attempt (created_at)');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE user_login_attempt');
    }
}
