<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUser extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `user`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `password` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
        $this->execute('INSERT INTO `user` (`id`, `password`) VALUES (1, "$2y$10$7y.Oblxw7VYGIkpSEymiAOG0zCOPasW3CmpT8wDDTNqUuQne5fsaa")');
    }
}
