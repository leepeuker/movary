<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImdbRatingToMovie extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie DROP COLUMN imdb_rating;
            ALTER TABLE movie DROP COLUMN updated_at_imdb;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie ADD COLUMN imdb_rating DOUBLE(3,1) DEFAULT NULL AFTER tmdb_poster_path;
            ALTER TABLE movie ADD COLUMN updated_at_imdb TIMESTAMP DEFAULT NULL AFTER updated_at_tmdb;
            SQL
        );
    }
}
