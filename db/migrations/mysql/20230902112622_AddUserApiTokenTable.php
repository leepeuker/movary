<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserApiTokenTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `user_api_token`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_api_token` (
                `user_id` INT(10) UNSIGNED NOT NULL,
                `token` CHAR(36) NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`token`),
                UNIQUE (`user_id`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
