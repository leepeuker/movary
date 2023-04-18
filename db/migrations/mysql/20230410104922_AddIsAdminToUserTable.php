<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIsAdminToUserTable extends AbstractMigration
{

    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN is_admin;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN is_admin TINYINT(1) DEFAULT 0 AFTER password;
            SQL,
        );
    }
}
