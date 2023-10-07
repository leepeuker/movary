<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDisplayCharacterNamesToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN display_character_names;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN display_character_names TINYINT(1) DEFAULT 1 AFTER country;
            SQL,
        );
    }
}
