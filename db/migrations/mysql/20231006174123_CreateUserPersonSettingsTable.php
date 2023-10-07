<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserPersonSettingsTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `user_person_settings`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `user_person_settings` (
                `user_id` INT(10) UNSIGNED NOT NULL,
                `person_id` INT(10) UNSIGNED NOT NULL,
                `is_hidden_in_top_lists` TINYINT(1) DEFAULT 0,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`user_id`, `person_id`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON DELETE CASCADE
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
