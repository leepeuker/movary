<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SetCastCharacterNameColumnToNullable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_movie_cast` (
                `person_id` INTEGER NOT NULL,
                `movie_id` INTEGER NOT NULL,
                `character_name` TEXT NOT NULL,
                `position` INTEGER DEFAULT NULL,
                FOREIGN KEY (`person_id`) REFERENCES person (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `position`)
            )
            SQL,
        );
        $this->execute('INSERT INTO `tmp_movie_cast` (person_id, movie_id, character_name, position) SELECT * FROM movie_cast');
        $this->execute('DROP TABLE `movie_cast`');
        $this->execute('ALTER TABLE `tmp_movie_cast` RENAME TO `movie_cast`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `tmp_movie_cast` (
                `person_id` INTEGER NOT NULL,
                `movie_id` INTEGER NOT NULL,
                `character_name` TEXT DEFAULT NULL,
                `position` INTEGER DEFAULT NULL,
                FOREIGN KEY (`person_id`) REFERENCES person (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `position`)
            )
            SQL,
        );
        $this->execute('INSERT INTO `tmp_movie_cast` (person_id, movie_id, character_name, position) SELECT * FROM movie_cast');
        $this->execute('DROP TABLE `movie_cast`');
        $this->execute('ALTER TABLE `tmp_movie_cast` RENAME TO `movie_cast`');
    }
}
