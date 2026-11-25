<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261101000000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add TMDB person external IDs';
    }

    public function isTransactional() : bool
    {
        return $this->platform instanceof SQLitePlatform;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof AbstractMySQLPlatform || $this->platform instanceof SQLitePlatform) {
            foreach (['facebook_id', 'instagram_id', 'tiktok_id', 'twitter_id', 'youtube_id', 'wikidata_id'] as $column) {
                $this->addSql("ALTER TABLE person ADD $column VARCHAR(255) DEFAULT NULL");
            }

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        if ($this->platform instanceof AbstractMySQLPlatform || $this->platform instanceof SQLitePlatform) {
            foreach (['facebook_id', 'instagram_id', 'tiktok_id', 'twitter_id', 'youtube_id', 'wikidata_id'] as $column) {
                $this->addSql("ALTER TABLE person DROP COLUMN $column");
            }

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }
}
