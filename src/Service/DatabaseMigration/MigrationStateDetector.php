<?php declare(strict_types=1);

namespace Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;

class MigrationStateDetector
{
    public const string DOCTRINE_BASELINE_VERSION = 'Movary\\DatabaseMigration\\Version20260928000000';

    private const string DOCTRINE_METADATA_TABLE = 'doctrine_migration_versions';

    private const string PHINX_METADATA_TABLE = 'phinxlog';

    /**
     * @param list<int> $expectedLegacyVersions
     */
    public function __construct(
        private readonly Connection $dbConnection,
        private readonly array $expectedLegacyVersions,
    ) {
    }

    public function detect() : MigrationState
    {
        $tableNames = array_map(
            static fn (string $tableName) : string => strtolower($tableName),
            $this->dbConnection->createSchemaManager()->listTableNames(),
        );

        if (in_array(self::DOCTRINE_METADATA_TABLE, $tableNames, true) === true) {
            $executedVersions = $this->dbConnection->fetchFirstColumn(
                'SELECT version FROM doctrine_migration_versions',
            );

            if (in_array(self::DOCTRINE_BASELINE_VERSION, $executedVersions, true)) {
                return MigrationState::DOCTRINE;
            }

            if ($executedVersions !== []) {
                return MigrationState::UNEXPECTED;
            }

            $tableNames = array_values(array_diff($tableNames, [self::DOCTRINE_METADATA_TABLE]));
        }

        if (in_array(self::PHINX_METADATA_TABLE, $tableNames, true) === true) {
            $executedVersions = array_map(
                static fn (string|int $version) : int => (int)$version,
                $this->dbConnection->fetchFirstColumn('SELECT version FROM phinxlog ORDER BY version'),
            );

            if ($executedVersions === $this->expectedLegacyVersions) {
                return MigrationState::LEGACY_READY;
            }

            $expectedPrefix = array_slice($this->expectedLegacyVersions, 0, count($executedVersions));

            return $executedVersions === $expectedPrefix
                ? MigrationState::LEGACY_INCOMPLETE
                : MigrationState::UNEXPECTED;
        }

        return $tableNames === [] ? MigrationState::EMPTY : MigrationState::UNEXPECTED;
    }
}
