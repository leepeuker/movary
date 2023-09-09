<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddRadarrUuidColumn extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN radarr_feed_uuid
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN radarr_feed_uuid CHAR(36) DEFAULT NULL AFTER plex_scrobble_ratings
            SQL,
        );
    }
}
