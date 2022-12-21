<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCacheTableForTmdbLanguages extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `cache_tmdb_languages`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `cache_tmdb_languages` (
                `iso_639_1` CHAR(2) NOT NULL,
                `english_name` VARCHAR(256) NOT NULL,
                PRIMARY KEY (`iso_639_1`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }
}
