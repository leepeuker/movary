<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLocationsFeatureFlagToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN locations_enabled;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN locations_enabled TINYINT(1) DEFAULT 1 AFTER display_character_names;
            SQL,
        );
    }
}
