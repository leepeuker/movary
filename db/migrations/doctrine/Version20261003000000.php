<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003000000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Hash authentication tokens at rest';
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql('ALTER TABLE user_auth_token MODIFY token CHAR(64) NOT NULL');
        }

        foreach ($this->connection->fetchAllAssociative('SELECT id, token FROM user_auth_token') as $tokenData) {
            $this->addSql(
                'UPDATE user_auth_token SET token = ? WHERE id = ?',
                [hash('sha256', $tokenData['token']), $tokenData['id']],
            );
        }
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException('Hashed authentication tokens cannot be converted back to plaintext.');
    }
}
