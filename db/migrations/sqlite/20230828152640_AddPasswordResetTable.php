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
                `user_id` INT(10) NOT NULL,
                `token` CHAR(36) NOT NULL,
                `expires_at` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`token`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
            )
            SQL,
        );
    }
}
