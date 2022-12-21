<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitialSqliteAdjustment extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE company MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            ALTER TABLE company MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL ON UPDATE NOW();
            ALTER TABLE genre MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            ALTER TABLE genre MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL ON UPDATE NOW();
            ALTER TABLE job_queue MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            ALTER TABLE job_queue MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL ON UPDATE NOW();
            ALTER TABLE movie MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            ALTER TABLE movie MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL ON UPDATE NOW();
            ALTER TABLE movie_user_rating MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            ALTER TABLE movie_user_rating MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL ON UPDATE NOW();
            ALTER TABLE person MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            ALTER TABLE person MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL ON UPDATE NOW();
            ALTER TABLE user MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            ALTER TABLE user_auth_token MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE company MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            ALTER TABLE company MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL;
            ALTER TABLE genre MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            ALTER TABLE genre MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL;
            ALTER TABLE job_queue MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            ALTER TABLE job_queue MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL;
            ALTER TABLE movie MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            ALTER TABLE movie MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL;
            ALTER TABLE movie_user_rating MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            ALTER TABLE movie_user_rating MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL;
            ALTER TABLE person MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            ALTER TABLE person MODIFY COLUMN updated_at TIMESTAMP DEFAULT NULL;
            ALTER TABLE user MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            ALTER TABLE user_auth_token MODIFY COLUMN created_at TIMESTAMP NOT NULL;
            SQL,
        );
    }
}
