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
                `user_id` INT(10) NOT NULL,
                `token` CHAR(36) NOT NULL,
                `created_at` TEXT NOT NULL,
                PRIMARY KEY (`token`),
                UNIQUE (`user_id`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
            )
            SQL,
        );
    }
}
