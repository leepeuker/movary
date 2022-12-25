<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SetCastCharacterNameColumnToNullable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute('ALTER TABLE movie_cast MODIFY character_name TEXT NOT NULL');
    }

    public function up() : void
    {
        $this->execute('ALTER TABLE movie_cast MODIFY character_name TEXT DEFAULT NULL');
    }
}
