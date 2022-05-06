<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangePrimaryKeyInTraktCache extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('ALTER TABLE `cache_trakt_user_movie_watched` ADD CONSTRAINT trakt_id PRIMARY KEY (trakt_id);');
    }

    public function up() : void
    {
        $this->execute('ALTER TABLE `cache_trakt_user_movie_watched` DROP PRIMARY KEY;');
    }
}
