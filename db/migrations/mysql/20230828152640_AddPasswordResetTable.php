<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPasswordResetTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `user_password_reset`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_password_reset` (
                `user_id` INT(10) UNSIGNED NOT NULL,
                `token` CHAR(36) NOT NULL,
                `expires_at` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`token`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
