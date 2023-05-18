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
                `movie_id` INTEGER,
                `user_id` INTEGER,
                `added_at` TEXT NOT NULL,
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE,
                UNIQUE (`movie_id`, `user_id`) 
            )
            SQL,
        );
    }
}
