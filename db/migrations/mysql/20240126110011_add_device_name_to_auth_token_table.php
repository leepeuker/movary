<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeviceNameToAuthTokenTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE `user_auth_token` DROP COLUMN device_name;
            ALTER TABLE `user_auth_token` DROP COLUMN user_agent_string;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE `user_auth_token` ADD COLUMN device_name VARCHAR(256);
            ALTER TABLE `user_auth_token` ADD COLUMN user_agent_string VARCHAR(256);
            SQL,
        );
    }
}
