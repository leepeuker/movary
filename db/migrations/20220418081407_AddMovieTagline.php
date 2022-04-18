<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMovieTagline extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie DROP COLUMN tagline;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie ADD COLUMN tagline TEXT DEFAULT NULL AFTER tmdb_id;
            SQL
        );
    }
}
