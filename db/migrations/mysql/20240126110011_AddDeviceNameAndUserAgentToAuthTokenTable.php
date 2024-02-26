<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeviceNameAndUserAgentToAuthTokenTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE `user_auth_token` DROP COLUMN device_name;
            ALTER TABLE `user_auth_token` DROP COLUMN user_agent;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            DELETE FROM `user_auth_token`;
            ALTER TABLE `user_auth_token` ADD COLUMN device_name VARCHAR(256) NOT NULL;
            ALTER TABLE `user_auth_token` ADD COLUMN user_agent TEXT NOT NULL;
            SQL,
        );
    }
}
