<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261015120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add pending user email addresses';
    }

    public function isTransactional() : bool
    {
        return $this->platform instanceof SQLitePlatform;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof AbstractMySQLPlatform) {
            $this->addSql('ALTER TABLE user ADD pending_email VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX unique_user_pending_email ON user (pending_email)');

            return;
        }

        if ($this->platform instanceof SQLitePlatform) {
            $this->addSql('ALTER TABLE user ADD pending_email VARCHAR(255) DEFAULT NULL');
            $this->addSql('CREATE UNIQUE INDEX unique_user_pending_email ON user (pending_email)');

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        if ($this->platform instanceof AbstractMySQLPlatform) {
            $this->addSql('DROP INDEX unique_user_pending_email ON user');
            $this->addSql('ALTER TABLE user DROP COLUMN pending_email');

            return;
        }

        if ($this->platform instanceof SQLitePlatform) {
            $this->addSql('DROP INDEX unique_user_pending_email');
            $this->addSql('ALTER TABLE user DROP COLUMN pending_email');

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }
}
