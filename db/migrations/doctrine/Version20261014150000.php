<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261014150000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Allow user accounts to be disabled';
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof AbstractMySQLPlatform) {
            $this->addSql('ALTER TABLE user ADD is_enabled TINYINT(1) NOT NULL DEFAULT 1');

            return;
        }

        if ($this->platform instanceof SQLitePlatform) {
            $this->addSql(
                'ALTER TABLE user ADD is_enabled BOOLEAN DEFAULT 1 NOT NULL CHECK (is_enabled IN (0, 1))',
            );

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN is_enabled');
    }
}
