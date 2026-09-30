<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Movary\DatabaseMigration\Version20260928000000;
use Movary\Service\DatabaseMigration\CutoverSchemaValidator;
use Movary\Service\DatabaseMigration\SchemaMismatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(CutoverSchemaValidator::class)]
#[CoversClass(SchemaMismatch::class)]
class CutoverSchemaValidatorTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
    }

    public function testAcceptsCanonicalSchema() : void
    {
        $connection = $this->createMigratedConnection();

        try {
            $validator = new CutoverSchemaValidator($connection);

            self::assertSame([], $validator->findDifferences());
            $validator->validate();
        } finally {
            $connection->close();
        }
    }

    public function testRejectsUnexpectedTable() : void
    {
        $connection = $this->createMigratedConnection();
        $connection->executeStatement('CREATE TABLE unknown_table (id INTEGER)');

        try {
            $validator = new CutoverSchemaValidator($connection);

            $this->expectException(SchemaMismatch::class);
            $this->expectExceptionMessage('Unexpected table: unknown_table');

            $validator->validate();
        } finally {
            $connection->close();
        }
    }

    public function testRejectsMissingRequiredIndex() : void
    {
        $connection = $this->createMigratedConnection();
        $connection->executeStatement('DROP INDEX index_job_status');

        try {
            $validator = new CutoverSchemaValidator($connection);

            $this->expectException(SchemaMismatch::class);
            $this->expectExceptionMessage('Missing index: job_queue(job_status)');

            $validator->validate();
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
        $migration = new Version20260928000000($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement());
        }

        return $connection;
    }
}
