<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDashboardSettingsToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN dashboard_extended_rows;
            ALTER TABLE user DROP COLUMN dashboard_visible_rows;
            ALTER TABLE user DROP COLUMN dashboard_order_rows;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN dashboard_visible_rows TEXT NULL AFTER is_admin;
            ALTER TABLE user ADD COLUMN dashboard_extended_rows TEXT NULL AFTER dashboard_visible_rows;
            ALTER TABLE user ADD COLUMN dashboard_order_rows TEXT NULL AFTER dashboard_extended_rows;
            SQL,
        );
    }
}
