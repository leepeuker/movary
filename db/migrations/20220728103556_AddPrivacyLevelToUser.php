<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPrivacyLevelToUser extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN privacy_level;
            SQL
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN privacy_level TINYINT UNSIGNED DEFAULT 1 AFTER password;
            SQL
        );
    }
}
