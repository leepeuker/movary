<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddMoreMovieDetails extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie DROP COLUMN overview;
            ALTER TABLE movie DROP COLUMN original_language;
            ALTER TABLE movie DROP COLUMN runtime;
            ALTER TABLE movie DROP COLUMN release_date;
            ALTER TABLE movie DROP COLUMN tmdb_vote_average;
            ALTER TABLE movie DROP COLUMN tmdb_vote_count;
            ALTER TABLE movie DROP COLUMN updated_at_tmdb;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie ADD COLUMN overview TEXT DEFAULT NULL AFTER tmdb_id;
            ALTER TABLE movie ADD COLUMN original_language VARCHAR(2) DEFAULT NULL AFTER overview;
            ALTER TABLE movie ADD COLUMN runtime SMALLINT DEFAULT NULL AFTER original_language;
            ALTER TABLE movie ADD COLUMN release_date DATE DEFAULT NULL AFTER runtime;
            ALTER TABLE movie ADD COLUMN tmdb_vote_average DECIMAL(3,1) DEFAULT NULL AFTER release_date;
            ALTER TABLE movie ADD COLUMN tmdb_vote_count SMALLINT DEFAULT NULL AFTER tmdb_vote_average;
            ALTER TABLE movie ADD COLUMN updated_at_tmdb DATETIME DEFAULT NULL AFTER updated_at;
            SQL
        );
    }
}
