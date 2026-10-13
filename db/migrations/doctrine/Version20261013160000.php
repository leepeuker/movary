<?php declare(strict_types=1);

namespace Movary\DatabaseMigration;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use RuntimeException;

final class Version20261013160000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add recurring job schedules';
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
                <<<SQL
                CREATE TABLE job_schedule (
                    id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                    job_type VARCHAR(64) NOT NULL,
                    cron_expression VARCHAR(255) NOT NULL,
                    user_id INT UNSIGNED DEFAULT NULL,
                    parameters TEXT NOT NULL,
                    enabled TINYINT(1) NOT NULL DEFAULT 1,
                    next_run_at DATETIME NOT NULL,
                    updated_at DATETIME DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    INDEX index_job_schedule_due (enabled, next_run_at),
                    INDEX index_job_schedule_user_id (user_id),
                    PRIMARY KEY(id),
                    CONSTRAINT job_schedule_ibfk_1
                        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
                SQL,
            );

            return;
        }

        if ($this->platform instanceof SqlitePlatform) {
            $this->addSql(
                <<<SQL
                CREATE TABLE job_schedule (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    job_type VARCHAR(64) NOT NULL,
                    cron_expression VARCHAR(255) NOT NULL,
                    user_id INTEGER UNSIGNED DEFAULT NULL,
                    parameters CLOB NOT NULL,
                    enabled BOOLEAN DEFAULT 1 NOT NULL CHECK (enabled IN (0, 1)),
                    next_run_at TEXT NOT NULL,
                    updated_at TEXT DEFAULT NULL,
                    created_at TEXT NOT NULL,
                    CONSTRAINT job_schedule_ibfk_1
                        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL
                            NOT DEFERRABLE INITIALLY IMMEDIATE
                )
                SQL,
            );
            $this->addSql('CREATE INDEX index_job_schedule_due ON job_schedule (enabled, next_run_at)');
            $this->addSql('CREATE INDEX index_job_schedule_user_id ON job_schedule (user_id)');

            return;
        }

        throw new RuntimeException('Unsupported database platform: ' . $this->platform::class);
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE job_schedule');
    }
}
