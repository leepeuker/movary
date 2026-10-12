<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261012120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Store original movie titles and each user\'s preferred title display';
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE movie ADD original_title VARCHAR(256) DEFAULT NULL');
        $this->addSql("ALTER TABLE user ADD movie_title_display VARCHAR(9) NOT NULL DEFAULT 'localized'");
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE movie DROP COLUMN original_title');
        $this->addSql('ALTER TABLE user DROP COLUMN movie_title_display');
    }
}
