<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261022120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add movie user reviews';
    }

    public function isTransactional() : bool
    {
        return $this->platform instanceof SqlitePlatform;
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function up(Schema $schema) : void
    {
        if ($this->platform instanceof MySQLPlatform) {
            $this->addSql(
                <<<'SQL'
                CREATE TABLE movie_user_review (
                    movie_id INT UNSIGNED NOT NULL,
                    user_id INT UNSIGNED NOT NULL,
                    review TEXT NOT NULL,
                    contains_spoilers TINYINT UNSIGNED DEFAULT 0 NOT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    PRIMARY KEY(movie_id, user_id),
                    CONSTRAINT movie_user_review_fk_movie_id
                        FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE,
                    CONSTRAINT movie_user_review_fk_user_id
                        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                SQL,
            );

            return;
        }

        if ($this->platform instanceof SqlitePlatform) {
            $this->addSql(
                <<<'SQL'
                CREATE TABLE movie_user_review (
                    movie_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    review TEXT NOT NULL,
                    contains_spoilers BOOLEAN DEFAULT 0 NOT NULL CHECK (contains_spoilers IN (0, 1)),
                    created_at TEXT NOT NULL,
                    updated_at TEXT DEFAULT NULL,
                    PRIMARY KEY(movie_id, user_id),
                    CONSTRAINT movie_user_review_fk_movie_id
                        FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
                    CONSTRAINT movie_user_review_fk_user_id
                        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
                )
                SQL,
            );

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        if ($this->platform instanceof MySQLPlatform || $this->platform instanceof SqlitePlatform) {
            $this->addSql('DROP TABLE movie_user_review');

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }
}
