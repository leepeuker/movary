<?php declare(strict_types=1);

namespace Movary\Service\DatabaseMigration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Movary\DatabaseMigration\Version20260928000000;

final class CutoverSchemaValidator
{
    private const array METADATA_TABLES = [
        'doctrine_migration_versions',
        'phinxlog',
    ];

    public function __construct(private readonly Connection $dbConnection)
    {
    }

    /**
     * @throws SchemaMismatch
     */
    public function validate() : void
    {
        $differences = $this->findDifferences();

        if ($differences !== []) {
            throw SchemaMismatch::create($differences);
        }
    }

    /**
     * @return list<string>
     */
    public function findDifferences() : array
    {
        $differences = [];
        $schemaManager = $this->dbConnection->createSchemaManager();
        $actualTableNames = array_map('strtolower', $schemaManager->listTableNames());
        $applicationTableNames = array_values(array_diff($actualTableNames, self::METADATA_TABLES));
        sort($applicationTableNames);

        $expectedSchema = Version20260928000000::createCutoverSchema();
        $expectedTableNames = array_map(
            static fn (Table $table) : string => strtolower($table->getName()),
            $expectedSchema->getTables(),
        );
        sort($expectedTableNames);

        foreach (array_diff($expectedTableNames, $applicationTableNames) as $tableName) {
            $differences[] = "Missing table: $tableName";
        }
        foreach (array_diff($applicationTableNames, $expectedTableNames) as $tableName) {
            $differences[] = "Unexpected table: $tableName";
        }

        foreach (array_intersect($expectedTableNames, $applicationTableNames) as $tableName) {
            $expectedTable = $expectedSchema->getTable($tableName);
            $actualTable = $schemaManager->introspectTable($tableName);
            array_push($differences, ...$this->compareTable($expectedTable, $actualTable));
        }

        array_push($differences, ...$this->checkValueDomainConstraints());

        return $differences;
    }

    /**
     * @return list<string>
     */
    private function compareTable(Table $expected, Table $actual) : array
    {
        $differences = [];
        $tableName = $expected->getName();
        $expectedColumnNames = array_keys($expected->getColumns());
        $actualColumnNames = array_keys($actual->getColumns());

        foreach (array_diff($expectedColumnNames, $actualColumnNames) as $columnName) {
            $differences[] = "Missing column: $tableName.$columnName";
        }
        foreach (array_diff($actualColumnNames, $expectedColumnNames) as $columnName) {
            $differences[] = "Unexpected column: $tableName.$columnName";
        }

        foreach (array_intersect($expectedColumnNames, $actualColumnNames) as $columnName) {
            array_push(
                $differences,
                ...$this->compareColumn(
                    $tableName,
                    $expected->getColumn($columnName),
                    $actual->getColumn($columnName),
                    in_array($columnName, $this->primaryKeyColumns($actual), true),
                ),
            );
        }

        if ($this->primaryKeyColumns($expected) !== $this->primaryKeyColumns($actual)) {
            $differences[] = "Primary key mismatch: $tableName";
        }

        if ($this->uniqueSignatures($expected) !== $this->actualUniqueSignatures($actual)) {
            $differences[] = "Unique constraint mismatch: $tableName";
        }

        foreach ($this->requiredIndexColumns($expected) as $requiredColumns) {
            if ($this->columnsAreIndexed($actual, $requiredColumns) === false) {
                $differences[] = "Missing index: $tableName(" . implode(', ', $requiredColumns) . ')';
            }
        }

        if ($this->foreignKeySignatures($expected) !== $this->foreignKeySignatures($actual)) {
            $differences[] = "Foreign key mismatch: $tableName";
        }

        return $differences;
    }

    /**
     * @return list<string>
     */
    private function compareColumn(
        string $tableName,
        Column $expected,
        Column $actual,
        bool $actualIsPrimary,
    ) : array {
        $differences = [];
        $columnName = $expected->getName();

        $actualNotnull = $actual->getNotnull() || $actualIsPrimary;
        if ($expected->getNotnull() !== $actualNotnull) {
            $differences[] = "Nullability mismatch: $tableName.$columnName";
        }

        if ($this->normalizeDefault($expected->getDefault()) !== $this->normalizeDefault($actual->getDefault())) {
            $differences[] = "Default mismatch: $tableName.$columnName";
        }

        if ($this->typesAreEquivalent($expected, $actual) === false) {
            $differences[] = "Type mismatch: $tableName.$columnName";
        }

        if ($this->dbConnection->getDatabasePlatform() instanceof AbstractMySQLPlatform
            && $expected->getUnsigned() !== $actual->getUnsigned()
        ) {
            $differences[] = "Unsigned mismatch: $tableName.$columnName";
        }

        return $differences;
    }

    private function typesAreEquivalent(Column $expected, Column $actual) : bool
    {
        $expectedType = $expected->getType()->getName();
        $actualType = $actual->getType()->getName();

        if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
            $groups = [
                ['boolean', 'smallint', 'integer', 'bigint'],
                ['string', 'text'],
                ['date', 'datetime', 'datetimetz', 'string', 'text'],
                ['decimal', 'float'],
            ];
        } else {
            $groups = [
                ['boolean', 'smallint', 'integer'],
                ['string', 'text'],
                ['datetime', 'datetimetz'],
                ['decimal', 'float'],
                ['date'],
            ];
        }

        foreach ($groups as $group) {
            if (in_array($expectedType, $group, true) && in_array($actualType, $group, true)) {
                return true;
            }
        }

        return $expectedType === $actualType;
    }

    private function normalizeDefault(mixed $default) : ?string
    {
        if ($default === null) {
            return null;
        }
        if (is_bool($default)) {
            return $default ? '1' : '0';
        }

        return trim((string)$default, "'\"");
    }

    /**
     * @return list<string>
     */
    private function primaryKeyColumns(Table $table) : array
    {
        return array_values($table->getPrimaryKey()?->getColumns() ?? []);
    }

    /**
     * @return list<string>
     */
    private function uniqueSignatures(Table $table) : array
    {
        $signatures = [];

        foreach ($table->getIndexes() as $index) {
            if ($index->isUnique() === false || $index->isPrimary() === true) {
                continue;
            }

            $columns = $index->getColumns();
            sort($columns);
            $signatures[] = implode(',', $columns);
        }

        sort($signatures);

        return $signatures;
    }

    /**
     * @return list<string>
     */
    private function actualUniqueSignatures(Table $table) : array
    {
        if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform === false) {
            return $this->uniqueSignatures($table);
        }

        $signatures = [];
        foreach ($this->sqliteIndexes($table->getName()) as $index) {
            if ($index['unique'] === true && $index['primary'] === false) {
                $columns = $index['columns'];
                sort($columns);
                $signatures[] = implode(',', $columns);
            }
        }
        sort($signatures);

        return $signatures;
    }

    /**
     * @return list<list<string>>
     */
    private function requiredIndexColumns(Table $table) : array
    {
        $columns = [];

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary() === false && $index->isUnique() === false) {
                $columns[] = array_values($index->getColumns());
            }
        }

        return $columns;
    }

    /**
     * @param list<string> $requiredColumns
     */
    private function columnsAreIndexed(Table $table, array $requiredColumns) : bool
    {
        if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
            foreach ($this->sqliteIndexes($table->getName()) as $index) {
                if (array_slice($index['columns'], 0, count($requiredColumns)) === $requiredColumns) {
                    return true;
                }
            }

            return false;
        }

        foreach ($table->getIndexes() as $index) {
            if (array_slice($index->getColumns(), 0, count($requiredColumns)) === $requiredColumns) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{columns: list<string>, primary: bool, unique: bool}>
     */
    private function sqliteIndexes(string $tableName) : array
    {
        $platform = $this->dbConnection->getDatabasePlatform();
        $tableIdentifier = $platform->quoteSingleIdentifier($tableName);
        $indexes = [];

        foreach ($this->dbConnection->fetchAllAssociative("PRAGMA index_list($tableIdentifier)") as $indexRow) {
            $indexName = (string)$indexRow['name'];
            $indexIdentifier = $platform->quoteSingleIdentifier($indexName);
            $columns = array_map(
                static fn (array $columnRow) : string => (string)$columnRow['name'],
                $this->dbConnection->fetchAllAssociative("PRAGMA index_info($indexIdentifier)"),
            );
            $indexes[] = [
                'columns' => $columns,
                'primary' => (string)$indexRow['origin'] === 'pk',
                'unique' => (int)$indexRow['unique'] === 1,
            ];
        }

        $primaryKey = $this->dbConnection->createSchemaManager()->introspectTable($tableName)->getPrimaryKey();
        if ($primaryKey !== null && count($primaryKey->getColumns()) === 1) {
            $indexes[] = [
                'columns' => array_values($primaryKey->getColumns()),
                'primary' => true,
                'unique' => true,
            ];
        }

        return $indexes;
    }

    /**
     * @return list<string>
     */
    private function foreignKeySignatures(Table $table) : array
    {
        $signatures = array_map(
            fn (ForeignKeyConstraint $foreignKey) : string => implode(',', $foreignKey->getLocalColumns())
                . '->' . strtolower($foreignKey->getForeignTableName())
                . '(' . implode(',', $foreignKey->getForeignColumns()) . ')'
                . '|delete=' . $this->normalizeForeignKeyAction($foreignKey->onDelete())
                . '|update=' . $this->normalizeForeignKeyAction($foreignKey->onUpdate()),
            $table->getForeignKeys(),
        );
        sort($signatures);

        return $signatures;
    }

    private function normalizeForeignKeyAction(?string $action) : string
    {
        $action = strtoupper($action ?? 'NO ACTION');

        return $action === 'RESTRICT' ? 'NO ACTION' : $action;
    }

    /**
     * @return list<string>
     */
    private function checkValueDomainConstraints() : array
    {
        if ($this->dbConnection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            return $this->checkMysqlValueDomainConstraints();
        }
        if ($this->dbConnection->getDatabasePlatform() instanceof SqlitePlatform) {
            return $this->checkSqliteValueDomainConstraints();
        }

        return ['Unsupported database platform'];
    }

    /**
     * @return list<string>
     */
    private function checkMysqlValueDomainConstraints() : array
    {
        $constraintNames = $this->dbConnection->fetchFirstColumn(
            <<<'SQL'
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND CONSTRAINT_TYPE = 'CHECK'
            SQL,
        );
        $constraintNames = array_map('strtolower', $constraintNames);
        $differences = [];

        foreach (['chk_person_gender', 'chk_user_mastodon_post_visibility'] as $constraintName) {
            if (in_array($constraintName, $constraintNames, true) === false) {
                $differences[] = "Missing check constraint: $constraintName";
            }
        }

        return $differences;
    }

    /**
     * @return list<string>
     */
    private function checkSqliteValueDomainConstraints() : array
    {
        $personSql = (string)$this->dbConnection->fetchOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'person'",
        );
        $userSql = (string)$this->dbConnection->fetchOne(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'user'",
        );
        $differences = [];

        if (preg_match('/CHECK\\s*\\(\\s*gender\\s+IN\\s*\\(\\s*0\\s*,\\s*1\\s*,\\s*2\\s*,\\s*3\\s*\\)\\s*\\)/i', $personSql) !== 1) {
            $differences[] = 'Missing check constraint: person.gender';
        }
        if (preg_match(
                "/CHECK\\s*\\(\\s*mastodon_post_visibility\\s+IN\\s*"
                . "\(\\s*'public'\\s*,\\s*'private'\\s*,\\s*'unlisted'\\s*,\\s*'direct'\\s*\)\\s*\)/i",
                $userSql,
            ) !== 1
        ) {
            $differences[] = 'Missing check constraint: user.mastodon_post_visibility';
        }

        return $differences;
    }
}
