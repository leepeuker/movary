<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddServerSettingTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE `server_setting`
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `server_setting` (
                `key` TEXT NOT NULL,
                `value` TEXT DEFAULT NULL,
                UNIQUE (`key`)
            )
            SQL,
        );
    }
}
