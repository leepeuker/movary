<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCinemaFlagToLocationTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `location_tmp` (
                `id` INTEGER NOT NULL,
                `user_id` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `location_tmp` (id, user_id, name, created_at, updated_at) 
            SELECT id, user_id, name, created_at, updated_at FROM location',
        );
        $this->execute('DROP TABLE `location`');
        $this->execute('ALTER TABLE `location_tmp` RENAME TO `location`');
    }

    public function up() : void
    {

        $this->execute(
            <<<SQL
            CREATE TABLE `location_tmp` (
                `id` INTEGER NOT NULL,
                `user_id` TEXT NOT NULL,
                `name` TEXT NOT NULL,
                `is_cinema` INTEGER DEFAULT 0,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute(
            'INSERT INTO `location_tmp` (id, user_id, name, created_at, updated_at) 
            SELECT * FROM location',
        );
        $this->execute('DROP TABLE `location`');
        $this->execute('ALTER TABLE `location_tmp` RENAME TO `location`');
    }
}
