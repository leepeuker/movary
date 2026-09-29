<?php declare(strict_types=1);

namespace Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;

class MigrationStateDetector
{
    public const int LEGACY_CUTOVER_VERSION = 20260927220000;

    private const string DOCTRINE_METADATA_TABLE = 'doctrine_migration_versions';

    private const string PHINX_METADATA_TABLE = 'phinxlog';

    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function detect() : MigrationState
    {
        $tableNames = array_map(
            static fn (string $tableName) : string => strtolower($tableName),
            $this->dbConnection->createSchemaManager()->listTableNames(),
        );

        if (in_array(self::DOCTRINE_METADATA_TABLE, $tableNames, true) === true) {
            $executedMigrationExists = (int)$this->dbConnection->fetchOne(
                'SELECT COUNT(*) FROM doctrine_migration_versions',
            ) > 0;

            if ($executedMigrationExists === true) {
                return MigrationState::DOCTRINE;
            }

            $tableNames = array_values(array_diff($tableNames, [self::DOCTRINE_METADATA_TABLE]));

            return $tableNames === [] ? MigrationState::EMPTY : MigrationState::UNEXPECTED;
        }

        if (in_array(self::PHINX_METADATA_TABLE, $tableNames, true) === true) {
            $latestVersion = (int)$this->dbConnection->fetchOne(
                'SELECT COALESCE(MAX(version), 0) FROM phinxlog',
            );

            return match (true) {
                $latestVersion === self::LEGACY_CUTOVER_VERSION => MigrationState::LEGACY_READY,
                $latestVersion < self::LEGACY_CUTOVER_VERSION => MigrationState::LEGACY_INCOMPLETE,
                default => MigrationState::UNEXPECTED,
            };
        }

        return $tableNames === [] ? MigrationState::EMPTY : MigrationState::UNEXPECTED;
    }
}
