<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserPasswordResetTokenTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `user_password_reset_token`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_password_reset_token` (
                `user_id` INT(10) UNSIGNED NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `expiration_date` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`user_id`),
                UNIQUE (`token_hash`),
                FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
