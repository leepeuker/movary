<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261014120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Store the last successful web login time for users';
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql('ALTER TABLE user ADD last_login_at DATETIME DEFAULT NULL');

            return;
        }

        if ($this->platform instanceof SqlitePlatform) {
            $this->addSql('ALTER TABLE user ADD last_login_at TEXT DEFAULT NULL');

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN last_login_at');
    }
}
