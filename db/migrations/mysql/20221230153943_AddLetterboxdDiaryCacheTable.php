<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLetterboxdDiaryCacheTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `cache_letterboxd_diary`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `cache_letterboxd_diary` (
                `diary_id` VARCHAR(255) NOT NULL,
                `letterboxd_id` VARCHAR(4) NOT NULL,
                PRIMARY KEY (`diary_id`)
            )
            SQL,
        );
    }
}
