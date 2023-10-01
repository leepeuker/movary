<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveUniqueNameAndCountryConstraintForCompany extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE company ADD CONSTRAINT name UNIQUE (name, origin_country);
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE company DROP CONSTRAINT name;
            SQL,
        );
    }
}
