<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLocationToUserWatchDatesTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE `movie_user_watch_dates` 
            DROP FOREIGN KEY `fk_movie_user_watch_dates_location_id`,
            DROP COLUMN `location_id`;
            SQL
        );

        $this->execute(
            <<<SQL
            DROP TABLE IF EXISTS `location`;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `location` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT(10) UNSIGNED NOT NULL,
                `name` TEXT NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            )
            SQL,
        );
        $this->execute(
            <<<SQL
            ALTER TABLE `movie_user_watch_dates` 
                ADD COLUMN `location_id` INT(10) UNSIGNED DEFAULT NULL AFTER `position`,
                ADD CONSTRAINT `fk_movie_user_watch_dates_location_id` FOREIGN KEY (`location_id`) REFERENCES `location` (`id`) ON DELETE CASCADE;
            SQL
        );
    }
}
