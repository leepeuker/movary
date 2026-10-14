<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261014200000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add user email verification state and tokens';
    }

    public function isTransactional() : bool
    {
        return $this->platform instanceof SqlitePlatform;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql('ALTER TABLE user ADD email_verified_at DATETIME DEFAULT NULL');
            $this->addSql('UPDATE user SET email_verified_at = CURRENT_TIMESTAMP');
            $this->addSql(
                <<<SQL
                CREATE TABLE user_email_verification_token (
                    user_id INT UNSIGNED NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    token_hash CHAR(64) NOT NULL,
                    expiration_date DATETIME NOT NULL,
                    created_at DATETIME NOT NULL,
                    UNIQUE INDEX unique_user_email_verification_token_hash (token_hash),
                    PRIMARY KEY(user_id),
                    CONSTRAINT user_email_verification_token_ibfk_1
                        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                SQL,
            );

            return;
        }

        if ($this->platform instanceof SqlitePlatform) {
            $this->addSql('ALTER TABLE user ADD email_verified_at TEXT DEFAULT NULL');
            $this->addSql('UPDATE user SET email_verified_at = CURRENT_TIMESTAMP');
            $this->addSql(
                <<<SQL
                CREATE TABLE user_email_verification_token (
                    user_id INTEGER NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    token_hash CHAR(64) NOT NULL,
                    expiration_date TEXT NOT NULL,
                    created_at TEXT NOT NULL,
                    PRIMARY KEY(user_id),
                    CONSTRAINT user_email_verification_token_ibfk_1
                        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                            NOT DEFERRABLE INITIALLY IMMEDIATE
                )
                SQL,
            );
            $this->addSql(
                'CREATE UNIQUE INDEX unique_user_email_verification_token_hash '
                . 'ON user_email_verification_token (token_hash)',
            );

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE user_email_verification_token');
        $this->addSql('ALTER TABLE user DROP COLUMN email_verified_at');
    }
}
