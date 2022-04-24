<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveTimeFromWatchDate extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_history MODIFY COLUMN watched_at DATETIME NOT NULL;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_history MODIFY COLUMN watched_at DATE NOT NULL;
            SQL
        );
    }
}
