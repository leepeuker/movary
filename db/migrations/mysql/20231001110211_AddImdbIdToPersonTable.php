<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddImdbIdToPersonTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person DROP COLUMN imdb_id;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE person ADD COLUMN imdb_id VARCHAR(10) DEFAULT NULL AFTER tmdb_id;
            SQL,
        );
    }
}
