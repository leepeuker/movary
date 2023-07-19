<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTmdbIsoCountryCacheTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `cache_tmdb_countries`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `cache_tmdb_countries` (
                `iso_3166_1` CHAR(2) NOT NULL,
                `english_name` VARCHAR(256) NOT NULL,
                PRIMARY KEY (`iso_3166_1`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
