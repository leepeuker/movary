<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeviceNameToAuthTokenTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_api_token_old` (
                `user_id` INT(10) NOT NULL,
                `token` CHAR(36) NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`token`),
                UNIQUE (`user_id`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute('INSERT INTO `user_api_token_old` (`user_id`, `token`, `created_at`) SELECT `user_id`, `token`, `created_at` FROM `user_api_token`');
        $this->execute('DROP TABLE `user_api_token`');
        $this->execute('ALTER TABLE `user_api_token_old` RENAME TO `user_api_token`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_api_token_new` (
                `user_id` INT(10) NOT NULL,
                `token` CHAR(36) NOT NULL,
                `device_name` VARCHAR(256) NOT NULL,
                `user_agent_string` VARCHAR(256) NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`token`),
                UNIQUE (`user_id`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute('INSERT INTO `user_api_token_new` (`user_id`, `token`, `created_at`) SELECT * FROM `user_api_token`');
        $this->execute('DELETE `user_api_token`');
        $this->execute('ALTER TABLE `user_api_token_new` RENAME TO `user_api_token`');
    }
}
