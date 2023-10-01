<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImdbIdToPersonTable extends AbstractMigration
{
    public function down()
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_person` (
                `id` INTEGER,
                `name` TEXT NOT NULL,
                `gender` TEXT NOT NULL,
                `known_for_department` TEXT DEFAULT NULL,
                `poster_path` TEXT DEFAULT NULL,
                `biography` TEXT DEFAULT NULL,
                `birth_date` TEXT DEFAULT NULL,
                `place_of_birth` TEXT DEFAULT NULL,
                `death_date` TEXT DEFAULT NULL,
                `tmdb_id` INTEGER NOT NULL,
                `tmdb_poster_path` TEXT DEFAULT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                `updated_at_tmdb` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`tmdb_id`)
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `tmp_person` (
                `id`,
                `name`,
                `gender`,
                `known_for_department`,
                `poster_path`,
                `biography`,
                `birth_date`,
                `place_of_birth`,
                `death_date`,
                `tmdb_id`,
                `tmdb_poster_path`,
                `created_at`,
                `updated_at`,
                `updated_at_tmdb`
            ) SELECT 
                `id`,
                `name`,
                `gender`,
                `known_for_department`,
                `poster_path`,
                `biography`,
                `birth_date`,
                `place_of_birth`,
                `death_date`,
                `tmdb_id`,
                `tmdb_poster_path`,
                `created_at`,
                `updated_at`,
                `updated_at_tmdb` 
            FROM `person`',
        );
        $this->execute('DROP TABLE `person`');
        $this->execute('ALTER TABLE `tmp_person` RENAME TO `person`');
    }

    public function up()
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_person` (
                `id` INTEGER,
                `name` TEXT NOT NULL,
                `gender` TEXT NOT NULL,
                `known_for_department` TEXT DEFAULT NULL,
                `poster_path` TEXT DEFAULT NULL,
                `biography` TEXT DEFAULT NULL,
                `birth_date` TEXT DEFAULT NULL,
                `place_of_birth` TEXT DEFAULT NULL,
                `death_date` TEXT DEFAULT NULL,
                `tmdb_id` INTEGER NOT NULL,
                `imdb_id` TEXT DEFAULT NULL,
                `tmdb_poster_path` TEXT DEFAULT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                `updated_at_tmdb` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`tmdb_id`)
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `tmp_person` (
                `id`,
                `name`,
                `gender`,
                `known_for_department`,
                `poster_path`,
                `biography`,
                `birth_date`,
                `place_of_birth`,
                `death_date`,
                `tmdb_id`,
                `tmdb_poster_path`,
                `created_at`,
                `updated_at`,
                `updated_at_tmdb`
            ) SELECT 
                `id`,
                `name`,
                `gender`,
                `known_for_department`,
                `poster_path`,
                `biography`,
                `birth_date`,
                `place_of_birth`,
                `death_date`,
                `tmdb_id`,
                `tmdb_poster_path`,
                `created_at`,
                `updated_at`,
                `updated_at_tmdb` 
            FROM `person`',
        );
        $this->execute('DROP TABLE `person`');
        $this->execute('ALTER TABLE `tmp_person` RENAME TO `person`');
    }
}
