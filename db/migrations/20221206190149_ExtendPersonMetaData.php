<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ExtendPersonMetaData extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person DROP COLUMN birth_date;
            ALTER TABLE person DROP COLUMN death_date;
            ALTER TABLE person DROP COLUMN place_of_birth;
            ALTER TABLE person DROP COLUMN updated_at_tmdb;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person ADD COLUMN birth_date DATE DEFAULT NULL AFTER poster_path;
            ALTER TABLE person ADD COLUMN place_of_birth VARCHAR(255) DEFAULT NULL AFTER birth_date;
            ALTER TABLE person ADD COLUMN death_date DATE DEFAULT NULL AFTER place_of_birth;
            ALTER TABLE person ADD COLUMN updated_at_tmdb DATETIME DEFAULT NULL AFTER tmdb_poster_path;
            SQL,
        );
    }
}
