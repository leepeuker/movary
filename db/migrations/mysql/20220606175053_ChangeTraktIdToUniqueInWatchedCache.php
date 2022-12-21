<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeTraktIdToUniqueInWatchedCache extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('DROP INDEX uniqueTraktId ON cache_trakt_user_movie_watched');
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `cache_trakt_user_movie_watched_tmp` (
                `trakt_id` INT(10) UNSIGNED NOT NULL,
                `last_updated_at` DATETIME NOT NULL
            ) COLLATE="utf8mb4_unicode_ci" ENGINE=InnoDB
            SQL
        );
        $this->execute(
            <<<SQL
            INSERT INTO cache_trakt_user_movie_watched_tmp (trakt_id, last_updated_at) 
            SELECT trakt_id, MAX(last_updated_at)
            FROM cache_trakt_user_movie_watched
            GROUP BY trakt_id
            SQL
        );
        $this->execute('DELETE FROM cache_trakt_user_movie_watched');
        $this->execute('ALTER TABLE cache_trakt_user_movie_watched ADD UNIQUE INDEX uniqueTraktId (trakt_id)');
        $this->execute(
            <<<SQL
            INSERT INTO cache_trakt_user_movie_watched (trakt_id, last_updated_at) 
            SELECT trakt_id, MAX(last_updated_at)
            FROM cache_trakt_user_movie_watched_tmp
            GROUP BY trakt_id
            SQL
        );
        $this->execute('DROP TABLE cache_trakt_user_movie_watched_tmp');
    }
}
