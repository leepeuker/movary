<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPosterPathToPerson extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person DROP COLUMN poster_path;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person ADD COLUMN poster_path VARCHAR(255) DEFAULT NULL AFTER tmdb_id;
            SQL
        );
    }
}
