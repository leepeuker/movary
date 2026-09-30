<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class StreamlineSchemaBeforeDoctrineCutover extends AbstractMigration
{
    public function down() : void
    {
        throw new RuntimeException('The MySQL schema streamlining is irreversible.');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user
                ALTER COLUMN display_tmdb_rating SET DEFAULT 1,
                ALTER COLUMN display_imdb_rating SET DEFAULT 1;

            ALTER TABLE cache_letterboxd_diary
                CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

            ALTER TABLE location
                CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL,
        );

        $this->dropIndexIfPresent(
            'cache_trakt_user_movie_rating',
            'cache_trakt_user_movie_rating_fk_user_id',
        );
        $this->dropIndexIfPresent(
            'cache_trakt_user_movie_watched',
            'cache_trakt_user_movie_watched_fk_user_id',
        );
    }

    private function dropIndexIfPresent(string $tableName, string $indexName) : void
    {
        $index = $this->fetchRow(
            sprintf(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS "
                . "WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s' AND INDEX_NAME = '%s' LIMIT 1",
                $tableName,
                $indexName,
            ),
        );

        if ($index !== false) {
            $this->execute("ALTER TABLE `$tableName` DROP INDEX `$indexName`");
        }
    }
}
