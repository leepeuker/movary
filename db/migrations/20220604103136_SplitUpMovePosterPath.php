<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SplitUpMovePosterPath extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person DROP COLUMN tmdb_poster_path;

            ALTER TABLE movie DROP COLUMN tmdb_poster_path;
            SQL
        );
    }

    public function up() : void
    {
        $columns = $this->fetchAll('SHOW COLUMNS FROM `person` LIKE "tmdb_poster_path"');

        if (empty($columns) === true) {
            $this->execute(
                <<<SQL
                ALTER TABLE person CHANGE COLUMN poster_path tmdb_poster_path VARCHAR(255) DEFAULT NULL;
                ALTER TABLE movie CHANGE COLUMN poster_path tmdb_poster_path VARCHAR(255) DEFAULT NULL;
                SQL
            );
        }

        $this->execute(
            <<<SQL
            ALTER TABLE person ADD COLUMN poster_path VARCHAR(255) DEFAULT NULL AFTER known_for_department;
            ALTER TABLE movie ADD COLUMN poster_path VARCHAR(255) DEFAULT NULL AFTER letterboxd_id;
            SQL
        );
    }
}
