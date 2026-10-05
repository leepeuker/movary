<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Movary\DatabaseMigration\Version20261005130000;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversNothing]
class CountryReferenceDataMigrationTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__, 4) . '/db/migrations/doctrine/Version20261005130000.php';
    }

    public function testPopulatesEmptyCountryTable() : void
    {
        $connection = $this->createConnection();

        try {
            $this->runMigration($connection);

            self::assertSame(251, $connection->fetchOne('SELECT COUNT(*) FROM country'));
            self::assertSame(
                'United States of America',
                $connection->fetchOne("SELECT english_name FROM country WHERE iso_3166_1 = 'US'"),
            );
        } finally {
            $connection->close();
        }
    }

    public function testPreservesExistingCountriesAndAddsMissingCountries() : void
    {
        $connection = $this->createConnection();
        $connection->insert('country', [
            'iso_3166_1' => 'US',
            'english_name' => 'Existing name',
            'created_at' => '2026-10-05 12:00:00',
        ]);

        try {
            $this->runMigration($connection);

            self::assertSame(251, $connection->fetchOne('SELECT COUNT(*) FROM country'));
            self::assertSame(
                'Existing name',
                $connection->fetchOne("SELECT english_name FROM country WHERE iso_3166_1 = 'US'"),
            );
        } finally {
            $connection->close();
        }
    }

    public function testIsIrreversible() : void
    {
        $connection = $this->createConnection();
        $migration = new Version20261005130000($connection, new NullLogger());

        try {
            $this->expectException(IrreversibleMigration::class);

            $migration->down(new Schema());
        } finally {
            $connection->close();
        }
    }

    private function createConnection() : Connection
    {
        $connection = DriverManager::getConnection([
            'driver' => 'sqlite3',
            'memory' => true,
        ]);
        $connection->executeStatement(
            'CREATE TABLE country ('
            . 'iso_3166_1 TEXT NOT NULL PRIMARY KEY, '
            . 'english_name TEXT NOT NULL, '
            . 'updated_at TEXT DEFAULT NULL, '
            . 'created_at TEXT NOT NULL'
            . ')',
        );

        return $connection;
    }

    private function runMigration(Connection $connection) : void
    {
        $migration = new Version20261005130000($connection, new NullLogger());
        $migration->up(new Schema());

        foreach ($migration->getSql() as $query) {
            $connection->executeStatement(
                $query->getStatement(),
                $query->getParameters(),
                $query->getTypes(),
            );
        }
    }
}
