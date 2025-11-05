<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMastodonDataToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user DROP COLUMN mastodon_enabled;
            ALTER TABLE user DROP COLUMN mastodon_username;
            ALTER TABLE user DROP COLUMN mastodon_access_token;
            ALTER TABLE user DROP COLUMN mastodon_post_automatic;
            ALTER TABLE user DROP COLUMN mastodon_post_visibility;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user ADD COLUMN mastodon_enabled TINYINT DEFAULT 0 NOT NULL AFTER display_imdb_rating;
            ALTER TABLE user ADD COLUMN mastodon_username TEXT DEFAULT NULL AFTER mastodon_enabled;
            ALTER TABLE user ADD COLUMN mastodon_access_token TEXT DEFAULT NULL AFTER mastodon_username;
            ALTER TABLE user ADD COLUMN mastodon_post_automatic TINYINT DEFAULT 1 NOT NULL AFTER mastodon_access_token;
            ALTER TABLE user ADD COLUMN mastodon_post_visibility ENUM('public','private','unlisted') DEFAULT "public" NOT NULL AFTER mastodon_post_automatic;
            SQL,
        );
    }
}

