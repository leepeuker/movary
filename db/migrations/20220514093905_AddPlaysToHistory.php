<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPlaysToHistory extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('ALTER TABLE movie_history DROP COLUMN plays');
        // $this->execute('ALTER TABLE movie_history DROP INDEX combinedKey');
    }

    public function up() : void
    {
        $this->execute('ALTER TABLE movie_history ADD COLUMN plays SMALLINT DEFAULT 1 AFTER watched_at');
        $this->execute('ALTER TABLE movie_history ADD UNIQUE INDEX combinedKey (movie_id, watched_at)');
    }
}
