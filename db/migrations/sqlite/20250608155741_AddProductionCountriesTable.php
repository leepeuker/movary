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
                `movie_id` TEXT NOT NULL,
                `iso_3166_1` TEXT NOT NULL,
                `position` INTEGER NOT NULL,
                `created_at` TEXT NOT NULL,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`iso_3166_1`) REFERENCES country (`iso_3166_1`) ON DELETE CASCADE
            )
            SQL,
        );
    }
}
