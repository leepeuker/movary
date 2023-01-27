<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBackdropPathToMovieTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie DROP COLUMN tmdb_backdrop_path;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie ADD COLUMN tmdb_backdrop_path VARCHAR(255) DEFAULT NULL AFTER tmdb_poster_path;
            SQL
        );
    }
}
