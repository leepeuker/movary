<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Movary\DatabaseMigration\Version20260928000000;
use Movary\DatabaseMigration\Version20261014200000;
use Movary\DatabaseMigration\Version20261015120000;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(Version20261015120000::class)]
class PendingEmailMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20261014200000.php';
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20261015120000.php';
    }

    public function testAddsNullablePendingEmailWithoutChangingExistingAccountState() : void
    {
        $connection = $this->createEmailVerificationConnection();
        $connection->insert('user', [
            'email' => 'alice@example.com',
            'email_verified_at' => '2026-10-14 20:00:00',
            'name' => 'Alice',
            'password' => 'password-hash',
            'created_at' => '2026-10-14 19:00:00',
        ]);

        try {
            $this->executeMigration($connection, new Version20261015120000($connection, new NullLogger()));

            $account = $connection->fetchAssociative(
                'SELECT email, pending_email, email_verified_at FROM user WHERE email = ?',
                ['alice@example.com'],
            );
            if ($account === false) {
                self::fail('Could not find migrated user.');
            }
            self::assertSame('alice@example.com', $account['email']);
            self::assertNull($account['pending_email']);
            self::assertSame('2026-10-14 20:00:00', $account['email_verified_at']);
        } finally {
            $connection->close();
        }
    }

    public function testPendingEmailIsUniqueWhileMultipleNullValuesAreAllowed() : void
    {
        $connection = $this->createEmailVerificationConnection();
        $this->executeMigration($connection, new Version20261015120000($connection, new NullLogger()));
        $this->insertUser($connection, 7, 'alice@example.com', 'Alice');
        $this->insertUser($connection, 8, 'bob@example.com', 'Bob');
        $connection->update('user', ['pending_email' => 'new@example.com'], ['id' => 7]);

        try {
            self::assertSame(1, (int)$connection->fetchOne('SELECT COUNT(*) FROM user WHERE pending_email IS NULL'));

            $this->expectException(UniqueConstraintViolationException::class);
            $connection->update('user', ['pending_email' => 'new@example.com'], ['id' => 8]);
        } finally {
            $connection->close();
        }
    }

    public function testMigrationCanBeRolledBack() : void
    {
        $connection = $this->createEmailVerificationConnection();

        try {
            $this->executeMigration($connection, new Version20261015120000($connection, new NullLogger()));
            $migration = new Version20261015120000($connection, new NullLogger());
            $migration->down(new Schema());
            $this->executeQueries($connection, $migration);

            self::assertArrayNotHasKey(
                'pending_email',
                $connection->createSchemaManager()->listTableColumns('user'),
            );
            self::assertArrayNotHasKey(
                'unique_user_pending_email',
                $connection->createSchemaManager()->listTableIndexes('user'),
            );
        } finally {
            $connection->close();
        }
    }

    private function createEmailVerificationConnection() : Connection
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        $this->executeMigration($connection, new Version20260928000000($connection, new NullLogger()));
        $this->executeMigration($connection, new Version20261014200000($connection, new NullLogger()));

        return $connection;
    }

    private function executeMigration(Connection $connection, AbstractMigration $migration) : void
    {
        $migration->up(new Schema());
        $this->executeQueries($connection, $migration);
    }

    private function executeQueries(Connection $connection, AbstractMigration $migration) : void
    {
        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement(), $query->getParameters(), $query->getTypes());
        }
    }

    private function insertUser(Connection $connection, int $id, string $email, string $name) : void
    {
        $connection->insert('user', [
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'password' => 'password-hash',
            'created_at' => '2026-10-15 12:00:00',
        ]);
    }
}
