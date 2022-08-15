<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateJobQueueTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE `job_queue`
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `job_queue` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `job` TEXT NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT NOW(),
                PRIMARY KEY (`id`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE `job_queue`
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `job_queue` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `job_type` TEXT NOT NULL,
                `job_status` TEXT NOT NULL,
                `user_id` INT(10) UNSIGNED DEFAULT NULL,
                `parameters` TEXT DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE NOW(),
                `created_at` DATETIME NOT NULL DEFAULT NOW(),
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES user (`id`)
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }
}
