<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddJobQueueTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE `job_queue`
            SQL
        );
    }

    public function up() : void
    {
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
}
