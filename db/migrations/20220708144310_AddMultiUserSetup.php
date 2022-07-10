<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMultiUserSetup extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN email;
            ALTER TABLE user DROP COLUMN name;
            ALTER TABLE user_auth_token DROP CONSTRAINT user_auth_token_fk_user_id;
            ALTER TABLE user_auth_token DROP COLUMN user_id;
            ALTER TABLE cache_trakt_user_movie_watched DROP CONSTRAINT cache_trakt_user_movie_watched_fk_user_id;
            ALTER TABLE cache_trakt_user_movie_watched DROP COLUMN user_id;
            ALTER TABLE cache_trakt_user_movie_rating DROP CONSTRAINT cache_trakt_user_movie_rating_fk_user_id;
            ALTER TABLE cache_trakt_user_movie_rating DROP COLUMN user_id;
            ALTER TABLE movie_user_watch_dates DROP CONSTRAINT movie_history_fk_user_id;
            ALTER TABLE movie_user_watch_dates DROP COLUMN user_id;
            DROP TABLE movie_user_rating;
            RENAME TABLE `movie_user_watch_dates` TO `movie_history`;
            ALTER TABLE movie ADD COLUMN personal_rating TINYINT UNSIGNED DEFAULT NULL;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            DELETE FROM user;
            ALTER TABLE user AUTO_INCREMENT = 1;
            ALTER TABLE user ADD COLUMN email VARCHAR(255) NOT NULL AFTER id;
            ALTER TABLE user ADD COLUMN name VARCHAR(255) DEFAULT NULL AFTER email;
            ALTER TABLE user ADD UNIQUE (email);
            ALTER TABLE user ADD UNIQUE (name);
            SQL
        );

        $this->execute(
            <<<SQL
            DELETE FROM user_auth_token;
            ALTER TABLE user_auth_token ADD COLUMN user_id INT(10) UNSIGNED NOT NULL AFTER id;
            ALTER TABLE user_auth_token ADD CONSTRAINT user_auth_token_fk_user_id FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE;
            SQL
        );

        $this->execute(
            <<<SQL
            DELETE FROM cache_trakt_user_movie_watched;
            ALTER TABLE cache_trakt_user_movie_watched ADD COLUMN user_id INT(10) UNSIGNED NOT NULL AFTER trakt_id;
            ALTER TABLE cache_trakt_user_movie_watched ADD CONSTRAINT cache_trakt_user_movie_watched_fk_user_id FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE;
            SQL
        );

        $this->execute(
            <<<SQL
            DELETE FROM cache_trakt_user_movie_rating;
            ALTER TABLE cache_trakt_user_movie_rating ADD COLUMN user_id INT(10) UNSIGNED NOT NULL AFTER trakt_id;
            ALTER TABLE cache_trakt_user_movie_rating ADD CONSTRAINT cache_trakt_user_movie_rating_fk_user_id FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE;
            SQL
        );

        $this->execute(
            <<<SQL
            ALTER TABLE movie_history ADD COLUMN user_id INT(10) UNSIGNED DEFAULT NULL AFTER movie_id;
            UPDATE movie_history SET user_id = 1;
            ALTER TABLE movie_history MODIFY COLUMN user_id INT(10) UNSIGNED NOT NULL;
            ALTER TABLE movie_history ADD CONSTRAINT movie_history_fk_user_id FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE;
            SQL
        );

        $this->execute(
            <<<SQL
            RENAME TABLE `movie_history` TO `movie_user_watch_dates`;
            SQL
        );

        $this->execute(
            <<<SQL
            CREATE TABLE `movie_user_rating` (
                `movie_id` INT(10) UNSIGNED NOT NULL,
                `user_id` INT(10) UNSIGNED NOT NULL,
                `rating` TINYINT NOT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE NOW(),
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`movie_id`, `user_id`),
                FOREIGN KEY (`movie_id`) REFERENCES `movie`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB;
            INSERT INTO movie_user_rating (movie_id, user_id, rating) (SELECT id, 1, personal_rating FROM movie WHERE personal_rating IS NOT NULL);
            ALTER TABLE movie DROP COLUMN personal_rating;
            SQL
        );
    }
}
