<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ChangeMovieTraktIdAndImdbIdToNullable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
                ALTER TABLE movie MODIFY COLUMN imdb_id VARCHAR(10) NOT NULL;
                ALTER TABLE movie MODIFY COLUMN trakt_id INT(10) UNSIGNED NOT NULL;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
                ALTER TABLE movie MODIFY COLUMN imdb_id VARCHAR(10) DEFAULT NULL;
                ALTER TABLE movie MODIFY COLUMN trakt_id INT(10) UNSIGNED DEFAULT NULL;
                SQL
        );
    }
}
