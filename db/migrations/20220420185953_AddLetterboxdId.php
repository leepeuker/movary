<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLetterboxdId extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie DROP COLUMN letterboxd_id;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE movie ADD COLUMN letterboxd_id CHAR(4) DEFAULT NULL AFTER tmdb_id;
            SQL
        );
    }
}
