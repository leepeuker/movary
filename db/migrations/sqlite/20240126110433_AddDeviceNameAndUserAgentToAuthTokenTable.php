<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDeviceNameAndUserAgentToAuthTokenTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_auth_token_tmp` (
                `id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `token` TEXT NOT NULL,
                `expiration_date` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute('INSERT INTO `user_auth_token_tmp` (`id`, `user_id`, `token`, `expiration_date`, `created_at`) SELECT `id`, `user_id`, `token`, `expiration_date`, `created_at` FROM `user_auth_token`');
        $this->execute('DROP TABLE `user_auth_token`');
        $this->execute('ALTER TABLE `user_auth_token_tmp` RENAME TO `user_auth_token`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_auth_token_tmp` (
                `id` INTEGER NOT NULL,
                `user_id` INT(10) NOT NULL,
                `token` CHAR(36) NOT NULL,
                `device_name` VARCHAR(256) NOT NULL,
                `user_agent` TEXT NOT NULL,
                `expiration_date` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute('DROP TABLE `user_auth_token`');
        $this->execute('ALTER TABLE `user_auth_token_tmp` RENAME TO `user_auth_token`');
    }
}
