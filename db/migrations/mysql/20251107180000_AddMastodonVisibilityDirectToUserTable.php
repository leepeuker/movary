<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMastodonVisibilityDirectToUserTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user MODIFY COLUMN mastodon_post_visibility ENUM('public','private','unlisted') DEFAULT "public" NOT NULL;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE user MODIFY COLUMN mastodon_post_visibility ENUM('public','private','unlisted','direct') DEFAULT "public" NOT NULL;
            SQL,
        );
    }
}

