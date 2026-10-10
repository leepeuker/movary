<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261010120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Store multiple personal API tokens as hashes with lifecycle metadata';
    }

    public function isTransactional() : bool
    {
        return $this->platform instanceof SqlitePlatform;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        $columns = $this->connection->createSchemaManager()->listTableColumns('user_api_token');
        if (isset($columns['token_hash']) === true && isset($columns['token']) === false) {
            return;
        }

        $legacyTokens = $this->connection->fetchAllAssociative(
            'SELECT user_id, token, created_at FROM user_api_token',
        );

        if ($this->platform instanceof MySQLPlatform) {
            $this->createMysqlTable();
        } elseif ($this->platform instanceof SqlitePlatform) {
            $this->createSqliteTable();
        } else {
            throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
        }

        foreach ($legacyTokens as $legacyToken) {
            $token = (string)$legacyToken['token'];

            $this->addSql(
                'INSERT INTO user_api_token_new '
                . '(user_id, name, token_hash, token_prefix, created_at, last_used_at, expires_at) '
                . 'VALUES (?, ?, ?, ?, ?, NULL, NULL)',
                [
                    $legacyToken['user_id'],
                    'Legacy token',
                    hash('sha256', $token),
                    substr($token, 0, 8),
                    $legacyToken['created_at'],
                ],
            );
        }

        $this->addSql('DROP TABLE user_api_token');

        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql('RENAME TABLE user_api_token_new TO user_api_token');

            return;
        }

        $this->addSql('ALTER TABLE user_api_token_new RENAME TO user_api_token');
        $this->addSql(
            'CREATE UNIQUE INDEX unique_user_api_token_token_hash ON user_api_token (token_hash)',
        );
        $this->addSql('CREATE INDEX index_user_api_token_user_id ON user_api_token (user_id)');
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException(
            'Hashed personal API tokens cannot be converted back to plaintext.',
        );
    }

    private function createMysqlTable() : void
    {
        $this->addSql(
            <<<SQL
            CREATE TABLE user_api_token_new (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                token_prefix VARCHAR(19) NOT NULL,
                created_at DATETIME NOT NULL,
                last_used_at DATETIME DEFAULT NULL,
                expires_at DATETIME DEFAULT NULL,
                UNIQUE INDEX unique_user_api_token_token_hash (token_hash),
                INDEX index_user_api_token_user_id (user_id),
                PRIMARY KEY(id),
                CONSTRAINT user_api_token_new_ibfk_1
                    FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL,
        );
    }

    private function createSqliteTable() : void
    {
        $this->addSql(
            <<<SQL
            CREATE TABLE user_api_token_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                token_prefix VARCHAR(19) NOT NULL,
                created_at TEXT NOT NULL,
                last_used_at TEXT DEFAULT NULL,
                expires_at TEXT DEFAULT NULL,
                CONSTRAINT user_api_token_ibfk_1
                    FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                        NOT DEFERRABLE INITIALLY IMMEDIATE
            )
            SQL,
        );
    }
}
