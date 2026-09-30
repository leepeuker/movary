<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Movary\Service\DatabaseMigration\MigrationState;
use Movary\Service\DatabaseMigration\MigrationStateDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MigrationStateDetector::class)]
class MigrationStateDetectorTest extends TestCase
{
    private const array EXPECTED_LEGACY_VERSIONS = [
        20260927143000,
        MigrationStateDetector::LEGACY_CUTOVER_VERSION,
    ];

    private Connection $dbConnection;

    private MigrationStateDetector $detector;

    protected function setUp() : void
    {
        $this->dbConnection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $this->detector = new MigrationStateDetector(
            $this->dbConnection,
            self::EXPECTED_LEGACY_VERSIONS,
        );
    }

    protected function tearDown() : void
    {
        $this->dbConnection->close();
    }

    public function testDetectsEmptyDatabase() : void
    {
        self::assertSame(MigrationState::EMPTY, $this->detector->detect());
    }

    public function testDetectsIncompleteLegacyDatabase() : void
    {
        $this->createPhinxMetadataTable();
        $this->dbConnection->insert('phinxlog', ['version' => 20260927143000]);

        self::assertSame(MigrationState::LEGACY_INCOMPLETE, $this->detector->detect());
    }

    public function testDetectsLegacyDatabaseAtCutoverBoundary() : void
    {
        $this->createPhinxMetadataTable();
        $this->insertCompleteLegacyHistory();

        self::assertSame(MigrationState::LEGACY_READY, $this->detector->detect());
    }

    public function testRejectsLegacyDatabasePastCutoverBoundary() : void
    {
        $this->createPhinxMetadataTable();
        $this->dbConnection->insert(
            'phinxlog',
            ['version' => MigrationStateDetector::LEGACY_CUTOVER_VERSION + 1],
        );

        self::assertSame(MigrationState::UNEXPECTED, $this->detector->detect());
    }

    public function testRejectsLegacyDatabaseWithMissingVersionBeforeCutoverBoundary() : void
    {
        $this->createPhinxMetadataTable();
        $this->dbConnection->insert(
            'phinxlog',
            ['version' => MigrationStateDetector::LEGACY_CUTOVER_VERSION],
        );

        self::assertSame(MigrationState::UNEXPECTED, $this->detector->detect());
    }

    public function testDetectsDoctrineManagedDatabase() : void
    {
        $this->createDoctrineMetadataTable();
        $this->dbConnection->insert(
            'doctrine_migration_versions',
            ['version' => MigrationStateDetector::DOCTRINE_BASELINE_VERSION],
        );

        self::assertSame(MigrationState::DOCTRINE, $this->detector->detect());
    }

    public function testRejectsDoctrineMetadataWithoutBaseline() : void
    {
        $this->createDoctrineMetadataTable();
        $this->dbConnection->insert(
            'doctrine_migration_versions',
            ['version' => 'Movary\\DatabaseMigration\\Version20990101000000'],
        );

        self::assertSame(MigrationState::UNEXPECTED, $this->detector->detect());
    }

    public function testTreatsEmptyDoctrineMetadataTableAsEmptyDatabase() : void
    {
        $this->createDoctrineMetadataTable();

        self::assertSame(MigrationState::EMPTY, $this->detector->detect());
    }

    public function testRecoversInterruptedDoctrineInitializationAsIncompleteLegacyDatabase() : void
    {
        $this->createPhinxMetadataTable();
        $this->createDoctrineMetadataTable();

        self::assertSame(MigrationState::LEGACY_INCOMPLETE, $this->detector->detect());
    }

    public function testRecoversInterruptedDoctrineInitializationAtCutoverBoundary() : void
    {
        $this->createPhinxMetadataTable();
        $this->insertCompleteLegacyHistory();
        $this->createDoctrineMetadataTable();

        self::assertSame(MigrationState::LEGACY_READY, $this->detector->detect());
    }

    public function testRejectsDatabaseWithoutMigrationMetadata() : void
    {
        $this->dbConnection->executeStatement('CREATE TABLE user (id INTEGER PRIMARY KEY)');

        self::assertSame(MigrationState::UNEXPECTED, $this->detector->detect());
    }

    private function createPhinxMetadataTable() : void
    {
        $this->dbConnection->executeStatement(
            'CREATE TABLE phinxlog (version BIGINT NOT NULL PRIMARY KEY)',
        );
    }

    private function createDoctrineMetadataTable() : void
    {
        $this->dbConnection->executeStatement(
            'CREATE TABLE doctrine_migration_versions (version TEXT NOT NULL)',
        );
    }

    private function insertCompleteLegacyHistory() : void
    {
        foreach (self::EXPECTED_LEGACY_VERSIONS as $version) {
            $this->dbConnection->insert('phinxlog', ['version' => $version]);
        }
    }
}
