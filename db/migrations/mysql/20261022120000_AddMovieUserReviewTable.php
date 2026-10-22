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
                `movie_id` INT UNSIGNED NOT NULL,
                `user_id` INT UNSIGNED NOT NULL,
                `review` TEXT NOT NULL,
                `contains_spoilers` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`movie_id`, `user_id`),
                CONSTRAINT `movie_user_review_fk_movie_id`
                    FOREIGN KEY (`movie_id`) REFERENCES `movie` (`id`) ON DELETE CASCADE,
                CONSTRAINT `movie_user_review_fk_user_id`
                    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL,
        );
    }
}
