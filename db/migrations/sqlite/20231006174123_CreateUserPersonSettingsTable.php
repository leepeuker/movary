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
                `user_id` INT(10) NOT NULL,
                `person_id` INT(10) NOT NULL,
                `is_hidden_in_top_lists` INTEGER DEFAULT 0,
                `updated_at` TEXT NOT NULL,
                PRIMARY KEY (`user_id`, `person_id`),
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`person_id`) REFERENCES `person`(`id`) ON DELETE CASCADE
            )
            SQL,
        );
    }
}
