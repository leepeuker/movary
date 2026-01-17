<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddJobStatusIndexToJobQueueTable extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            DROP INDEX index_job_status;
            DROP INDEX index_job_type;
            SQL,
        );
    }

    public function up() : void
    {
        $this->execute(
            <<<SQL
            CREATE INDEX index_job_status ON job_queue(job_status);
            CREATE INDEX index_job_type ON job_queue(job_type);
            SQL,
        );
    }
}

