<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserAuthTokenTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE `user_auth_token`
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_auth_token` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `token` VARCHAR(255) NOT NULL,
                `expiration_date` DATETIME NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT NOW(),
                PRIMARY KEY (`id`),
                UNIQUE (`token`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }
}
