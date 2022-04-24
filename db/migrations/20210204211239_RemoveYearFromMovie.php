<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class RemoveYearFromMovie extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie ADD COLUMN year YEAR NOT NULL AFTER tmdb_id;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie DROP COLUMN year;
            SQL
        );
    }
}
