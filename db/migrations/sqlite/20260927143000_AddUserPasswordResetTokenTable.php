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
                `user_id` INTEGER NOT NULL,
                `token_hash` TEXT NOT NULL,
                `expiration_date` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`user_id`),
                UNIQUE (`token_hash`),
                FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            )
            SQL,
        );
    }
}
