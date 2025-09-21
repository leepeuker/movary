<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddHideTmdbAndImdbRatingsToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN hide_tmdb_rating;
            ALTER TABLE user DROP COLUMN hide_imdb_rating;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN display_tmdb_rating TINYINT DEFAULT 0 NOT NULL AFTER locations_enabled;
            ALTER TABLE user ADD COLUMN display_imdb_rating TINYINT DEFAULT 0 NOT NULL AFTER display_tmdb_rating;
            SQL,
        );
    }
}

