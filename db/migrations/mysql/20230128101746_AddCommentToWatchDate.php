<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCommentToWatchDate extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates DROP COLUMN comment;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie_user_watch_dates ADD COLUMN comment TEXT DEFAULT NULL AFTER plays;
            SQL
        );
    }
}
