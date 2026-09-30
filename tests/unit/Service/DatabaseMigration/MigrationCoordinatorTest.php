<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Movary\DatabaseMigration\Version20260928000000;
use Movary\Service\DatabaseMigration\CutoverSchemaValidator;
use Movary\Service\DatabaseMigration\MigrationCoordinator;
use Movary\Service\DatabaseMigration\MigrationState;
use Movary\Service\DatabaseMigration\MigrationStateDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(MigrationCoordinator::class)]
class MigrationCoordinatorTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20260928000000.php';
    }

    public function testMigratesEmptyDatabaseToDoctrineBaseline() : void
    {
        $connection = $this->createConnection();

        try {
            $state = $this->createCoordinator($connection)->migrate();

            self::assertSame(MigrationState::EMPTY, $state);
            self::assertSame(
                MigrationState::DOCTRINE,
                (new MigrationStateDetector($connection, $this->expectedLegacyVersions()))->detect(),
            );
            self::assertCount(27, $connection->createSchemaManager()->listTableNames());
        } finally {
            $connection->close();
        }
    }

    public function testMarksValidatedLegacySchemaWithoutRecreatingIt() : void
    {
        $connection = $this->createConnection();
        $this->executeBaseline($connection);
        $connection->executeStatement('CREATE TABLE phinxlog (version BIGINT NOT NULL PRIMARY KEY)');
        foreach ($this->expectedLegacyVersions() as $version) {
            $connection->insert('phinxlog', ['version' => $version]);
        }
        $connection->insert('server_setting', ['key' => 'test', 'value' => 'preserved']);

        try {
            $state = $this->createCoordinator($connection)->migrate();

            self::assertSame(MigrationState::LEGACY_READY, $state);
            self::assertSame(
                MigrationStateDetector::DOCTRINE_BASELINE_VERSION,
                $connection->fetchOne('SELECT version FROM doctrine_migration_versions'),
            );
            self::assertSame(
                'preserved',
                $connection->fetchOne("SELECT value FROM server_setting WHERE key = 'test'"),
            );
        } finally {
            $connection->close();
        }
    }

    public function testDefersIncompleteLegacyDatabaseToPhinx() : void
    {
        $connection = $this->createConnection();
        $connection->executeStatement('CREATE TABLE phinxlog (version BIGINT NOT NULL PRIMARY KEY)');
        $connection->insert('phinxlog', ['version' => 20260927143000]);

        try {
            $state = $this->createCoordinator($connection)->migrate();

            self::assertSame(MigrationState::LEGACY_INCOMPLETE, $state);
            self::assertNotContains(
                'doctrine_migration_versions',
                $connection->createSchemaManager()->listTableNames(),
            );
        } finally {
            $connection->close();
        }
    }

    private function createConnection() : Connection
    {
        return DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
    }

    private function createCoordinator(Connection $connection) : MigrationCoordinator
    {
        $dependencyFactory = DependencyFactory::fromConnection(
            new ConfigurationArray([
                'migrations_paths' => [
                    'Movary\\DatabaseMigration' => dirname(__DIR__, 4) . '/db/migrations/doctrine',
                ],
                'table_storage' => ['table_name' => 'doctrine_migration_versions'],
                'transactional' => false,
            ]),
            new ExistingConnection($connection),
            new NullLogger(),
        );

        return new MigrationCoordinator(
            new MigrationStateDetector($connection, $this->expectedLegacyVersions()),
            new CutoverSchemaValidator($connection),
            $dependencyFactory,
        );
    }

    private function executeBaseline(Connection $connection) : void
    {
        $migration = new Version20260928000000($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement($query->getStatement());
        }
    }

    /**
     * @return list<int>
     */
    private function expectedLegacyVersions() : array
    {
        return [
            20260927143000,
            MigrationStateDetector::LEGACY_CUTOVER_VERSION,
        ];
    }
}
