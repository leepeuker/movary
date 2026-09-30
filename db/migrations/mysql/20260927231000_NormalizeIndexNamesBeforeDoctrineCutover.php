<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NormalizeIndexNamesBeforeDoctrineCutover extends AbstractMigration
{
    public function down() : void
    {
        throw new RuntimeException('The MySQL index name normalization is irreversible.');
    }

    public function up() : void
    {
        $this->normalizeIndexName('job_queue', 'user_id', 'job_queue_ibfk_1');
        $this->normalizeIndexName('movie_cast', 'person_id', 'movie_cast_ibfk_1');
        $this->normalizeIndexName('movie_crew', 'person_id', 'movie_crew_ibfk_1');
    }

    private function normalizeIndexName(string $tableName, string $legacyName, string $canonicalName) : void
    {
        $legacySignature = $this->indexSignature($tableName, $legacyName);
        $canonicalSignature = $this->indexSignature($tableName, $canonicalName);

        if ($legacySignature === null && $canonicalSignature !== null) {
            return;
        }
        if ($legacySignature === null) {
            throw new RuntimeException("Missing expected index on $tableName.");
        }
        if ($canonicalSignature === null) {
            $this->execute("ALTER TABLE `$tableName` RENAME INDEX `$legacyName` TO `$canonicalName`");

            return;
        }
        if ($legacySignature !== $canonicalSignature) {
            throw new RuntimeException("Conflicting indexes exist on $tableName.");
        }

        $this->execute("ALTER TABLE `$tableName` DROP INDEX `$legacyName`");
    }

    private function indexSignature(string $tableName, string $indexName) : ?string
    {
        $row = $this->fetchRow(
            sprintf(
                "SELECT CONCAT(NON_UNIQUE, ':', GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)) signature "
                . "FROM information_schema.STATISTICS "
                . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND INDEX_NAME = '%s' "
                . 'GROUP BY NON_UNIQUE',
                $tableName,
                $indexName,
            ),
        );

        return $row === false ? null : (string)$row['signature'];
    }
}
