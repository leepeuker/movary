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
                `iso_3166_1` TEXT,
                `english_name` TEXT NOT NULL,
                PRIMARY KEY (`iso_3166_1`)
            )
            SQL,
        );
    }
}
