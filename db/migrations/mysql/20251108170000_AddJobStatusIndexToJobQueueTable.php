<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddJobStatusIndexToJobQueueTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE job_queue DROP INDEX index_job_status;
            ALTER TABLE job_queue DROP INDEX index_job_type;
            ALTER TABLE job_queue MODIFY COLUMN job_status TEXT NOT NULL;
            ALTER TABLE job_queue MODIFY COLUMN job_type TEXT NOT NULL;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            ALTER TABLE job_queue MODIFY COLUMN job_status VARCHAR(32) NOT NULL;
            ALTER TABLE job_queue MODIFY COLUMN job_type VARCHAR(64) NOT NULL;
            ALTER TABLE job_queue ADD INDEX index_job_status (job_status);
            ALTER TABLE job_queue ADD INDEX index_job_type (job_type);
            SQL,
        );
    }
}

