<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Movary\Service\DatabaseMigration\CanonicalSchemaProvider;

final class Version20260928000000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create the canonical Movary schema at the Phinx cutover boundary';
    }

    public function isTransactional() : bool
    {
        return false;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        $targetSchema = (new CanonicalSchemaProvider())->createSchema();

        if ($this->platform instanceof SqlitePlatform) {
            $targetSchema->getTable('person')->getColumn('gender')->setColumnDefinition(
                'SMALLINT NOT NULL CHECK (gender IN (0, 1, 2, 3))',
            );
            $targetSchema->getTable('user')->getColumn('mastodon_post_visibility')->setColumnDefinition(
                "VARCHAR(16) NOT NULL DEFAULT 'public' "
                . "CHECK (mastodon_post_visibility IN ('public', 'private', 'unlisted', 'direct'))",
            );
        }

        foreach ($targetSchema->toSql($this->platform) as $sql) {
            $this->addSql($sql);
        }

        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql(
                'ALTER TABLE person ADD CONSTRAINT chk_person_gender '
                . 'CHECK (gender IN (0, 1, 2, 3))',
            );
            $this->addSql(
                'ALTER TABLE user ADD CONSTRAINT chk_user_mastodon_post_visibility '
                . "CHECK (mastodon_post_visibility IN ('public', 'private', 'unlisted', 'direct'))",
            );
        }
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException(
            'The Movary baseline cannot be rolled back because doing so would delete all application data.',
        );
    }
}
