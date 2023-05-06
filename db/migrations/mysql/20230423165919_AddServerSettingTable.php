<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddServerSettingTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `server_setting`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `server_setting` (
                `key` VARCHAR(255) NOT NULL,
                `value` VARCHAR(255) DEFAULT NULL,
                UNIQUE (`key`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
