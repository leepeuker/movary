<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddWatchlistTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `watchlist`');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `watchlist` (
                `movie_id` INT(10) UNSIGNED,
                `user_id` INT(10) UNSIGNED,
                `added_at` TIMESTAMP NOT NULL,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `user_id`) 
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
