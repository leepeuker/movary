<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBiographyToPerson extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person DROP COLUMN biography;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person ADD COLUMN biography TEXT NULL AFTER birth_date;
            SQL,
        );
    }
}
