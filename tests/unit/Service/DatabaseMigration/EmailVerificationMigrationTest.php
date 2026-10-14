<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;
use Movary\DatabaseMigration\Version20260928000000;
use Movary\DatabaseMigration\Version20261014200000;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(Version20261014200000::class)]
class EmailVerificationMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20261014200000.php';
    }

    public function testAddsVerificationSchemaAndBackfillsExistingUsers() : void
    {
        $connection = $this->createBaselineConnection();
        $connection->insert('user', [
            'email' => 'alice@example.com',
            'name' => 'Alice',
            'password' => 'password-hash',
            'created_at' => '2026-10-14 19:00:00',
        ]);

        try {
            $this->executeMigration($connection, new Version20261014200000($connection, new NullLogger()));

            self::assertNotFalse(
                $connection->fetchOne('SELECT email_verified_at FROM user WHERE email = ?', ['alice@example.com']),
            );
            self::assertContains(
                'user_email_verification_token',
                $connection->createSchemaManager()->listTableNames(),
            );

            $connection->insert('user', [
                'email' => 'bob@example.com',
                'name' => 'Bob',
                'password' => 'password-hash',
                'created_at' => '2026-10-14 20:00:00',
            ]);
            self::assertNull(
                $connection->fetchOne('SELECT email_verified_at FROM user WHERE email = ?', ['bob@example.com']),
            );
        } finally {
            $connection->close();
        }
    }

    public function testVerificationTokenIsDeletedWithUser() : void
    {
        $connection = $this->createBaselineConnection();
        $connection->insert('user', [
            'id' => 7,
            'email' => 'alice@example.com',
            'name' => 'Alice',
            'password' => 'password-hash',
            'created_at' => '2026-10-14 19:00:00',
        ]);

        try {
            $this->executeMigration($connection, new Version20261014200000($connection, new NullLogger()));
            $connection->insert('user_email_verification_token', [
                'user_id' => 7,
                'email' => 'alice@example.com',
                'token_hash' => str_repeat('a', 64),
                'expiration_date' => '2026-10-15 20:00:00',
                'created_at' => '2026-10-14 20:00:00',
            ]);

            $connection->delete('user', ['id' => 7]);

            self::assertSame(0, (int)$connection->fetchOne('SELECT COUNT(*) FROM user_email_verification_token'));
        } finally {
            $connection->close();
        }
    }

    public function testVerificationTokenHashesAreUnique() : void
    {
        $connection = $this->createBaselineConnection();
        $connection->insert('user', [
            'id' => 7,
            'email' => 'alice@example.com',
            'name' => 'Alice',
            'password' => 'password-hash',
            'created_at' => '2026-10-14 19:00:00',
        ]);
        $connection->insert('user', [
            'id' => 8,
            'email' => 'bob@example.com',
            'name' => 'Bob',
            'password' => 'password-hash',
            'created_at' => '2026-10-14 19:00:00',
        ]);

        try {
            $this->executeMigration($connection, new Version20261014200000($connection, new NullLogger()));
            $token = [
                'token_hash' => str_repeat('a', 64),
                'expiration_date' => '2026-10-15 20:00:00',
                'created_at' => '2026-10-14 20:00:00',
            ];
            $connection->insert('user_email_verification_token', [
                'user_id' => 7,
                'email' => 'alice@example.com',
                ...$token,
            ]);

            $this->expectException(UniqueConstraintViolationException::class);
            $connection->insert('user_email_verification_token', [
                'user_id' => 8,
                ...$token,
                'email' => 'bob@example.com',
            ]);
        } finally {
            $connection->close();
        }
    }

    public function testMigrationCanBeRolledBack() : void
    {
        $connection = $this->createBaselineConnection();

        try {
            $this->executeMigration($connection, new Version20261014200000($connection, new NullLogger()));
            $migration = new Version20261014200000($connection, new NullLogger());
            $migration->down(new Schema());
            $this->executeQueries($connection, $migration);

            self::assertNotContains(
                'user_email_verification_token',
                $connection->createSchemaManager()->listTableNames(),
            );
            self::assertArrayNotHasKey(
                'email_verified_at',
                $connection->createSchemaManager()->listTableColumns('user'),
            );
        } finally {
            $connection->close();
        }
    }

    private function createBaselineConnection() : Connection
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $migration = new Version20260928000000($connection, new NullLogger());
        $migration->up(new Schema());
        $this->executeQueries($connection, $migration);

        return $connection;
    }

    private function executeMigration(Connection $connection, Version20261014200000 $migration) : void
    {
        $migration->up(new Schema());
        $this->executeQueries($connection, $migration);
    }

    private function executeQueries(Connection $connection, \Doctrine\Migrations\AbstractMigration $migration) : void
    {
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }
}
