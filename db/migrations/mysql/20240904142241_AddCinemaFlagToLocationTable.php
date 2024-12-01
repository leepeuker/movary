<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCinemaFlagToLocationTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE location DROP COLUMN is_cinema;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE location ADD COLUMN is_cinema TINYINT(1) DEFAULT 0 AFTER name;
            SQL,
        );
    }
}
