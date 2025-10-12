<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddProductionCountriesTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE movie_production_countries');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `movie_production_countries` (
                `movie_id` INT UNSIGNED NOT NULL,
                `iso_3166_1` CHAR(2) NOT NULL,
                `position` TINYINT NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT NOW(),
                FOREIGN KEY (`movie_id`) REFERENCES `movie` (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`iso_3166_1`) REFERENCES `country` (`iso_3166_1`) ON DELETE CASCADE,
                PRIMARY KEY (`movie_id`, `iso_3166_1`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            SQL,
        );
    }
}
