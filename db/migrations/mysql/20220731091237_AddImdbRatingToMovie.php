<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImdbRatingToMovie extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie DROP COLUMN imdb_rating_average;
            ALTER TABLE movie DROP COLUMN imdb_rating_vote_count;
            ALTER TABLE movie DROP COLUMN updated_at_imdb;
            ALTER TABLE user MODIFY COLUMN tmdb_vote_count SMALLINT DEFAULT NULL;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie ADD COLUMN imdb_rating_average DOUBLE(3,1) DEFAULT NULL AFTER tmdb_poster_path;
            ALTER TABLE movie ADD COLUMN imdb_rating_vote_count INT UNSIGNED DEFAULT NULL AFTER imdb_rating_average;
            ALTER TABLE movie ADD COLUMN updated_at_imdb TIMESTAMP NULL AFTER updated_at_tmdb;
            ALTER TABLE movie MODIFY COLUMN tmdb_vote_count INT UNSIGNED DEFAULT NULL;
            SQL
        );
    }
}
