<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveScanLog extends AbstractMigration
{
    public function up() : void
    {
        $this->execute(
            <<<SQL
            DROP TABLE movary.`sync_log`
            SQL
        );
    }

    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `sync_log` (
                `type` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT NOW()
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
    }
}
