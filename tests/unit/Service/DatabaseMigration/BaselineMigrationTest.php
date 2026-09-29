<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Movary\DatabaseMigration\Version20260928000000;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversNothing]
class BaselineMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
    }

    public function testCreatesCanonicalSqliteSchema() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $tableNames = $connection->createSchemaManager()->listTableNames();

            self::assertCount(26, $tableNames);
            self::assertContains('user_password_reset_token', $tableNames);
        } finally {
            $connection->close();
        }
    }

    public function testRejectsUnsupportedPersonGenderOnSqlite() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $this->expectException(Exception::class);
            $connection->insert('person', [
                'name' => 'Invalid gender',
                'gender' => 9,
                'created_at' => '2026-01-01 00:00:00',
            ]);
        } finally {
            $connection->close();
        }
    }

    public function testRejectsUnsupportedMastodonVisibilityOnSqlite() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $this->expectException(Exception::class);
            $connection->insert('user', [
                'email' => 'user@example.test',
                'name' => 'user',
                'password' => 'secret',
                'mastodon_post_visibility' => 'followers',
                'created_at' => '2026-01-01 00:00:00',
            ]);
        } finally {
            $connection->close();
        }
    }

    public function testIsNonTransactionalAndIrreversible() : void
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $migration = new Version20260928000000($connection, new NullLogger());

        try {
            self::assertFalse($migration->isTransactional());
            $this->expectException(IrreversibleMigration::class);

            $migration->down(new Schema());
        } finally {
            $connection->close();
        }
    }

    private function createMigratedConnection() : Connection
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $connection->executeStatement('PRAGMA foreign_keys = ON');

        $migration = new Version20260928000000($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement());
        }

        return $connection;
    }
}
