<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMovieUserReviewTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP TABLE `movie_user_review`');
    }

    public function up() : void
    {
        $this->execute(
            <<<'SQL'
            CREATE TABLE `movie_user_review` (
                `movie_id` INTEGER NOT NULL,
                `user_id` INTEGER NOT NULL,
                `review` TEXT NOT NULL,
                `contains_spoilers` INTEGER NOT NULL DEFAULT 0,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`movie_id`, `user_id`),
                FOREIGN KEY (`movie_id`) REFERENCES movie (`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES user (`id`) ON DELETE CASCADE
            )
            SQL,
        );
    }
}
