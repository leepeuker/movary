<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDashboardSettingsToUserTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN dashboard_order_of_rows;
            ALTER TABLE user DROP COLUMN dashboard_hidden_rows;
            ALTER TABLE user DROP COLUMN dashboard_visible_rows;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN dashboard_order_of_rows VARCHAR(255) NULL AFTER is_admin;
            ALTER TABLE user ADD COLUMN dashboard_hidden_rows VARCHAR(255) NULL AFTER dashboard_order_of_rows;
            ALTER TABLE user ADD COLUMN dashboard_visible_rows VARCHAR(255) NULL AFTER dashboard_hidden_rows;
            SQL,
        );
    } 
}
