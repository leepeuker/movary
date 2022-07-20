<?php declare(strict_types=1);

namespace Movary\Worker;

use Doctrine\DBAL\Connection;
use Movary\Util\Json;

class Repository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function addJob(Job $job) : void
    {
        $this->dbConnection->insert(
            'job_queue',
            [
                'job' => Json::encode($job),
            ]
        );
    }

    public function fetchOldestJob() : ?Job
    {
        $this->dbConnection->beginTransaction();

        $data = $this->dbConnection->fetchAssociative('SELECT * FROM `job_queue` ORDER BY `created_at` LIMIT 1');

        if ($data === false) {
            return null;
        }

        $this->deleteJob($data['id']);

        $this->dbConnection->commit();

        return Job::createFromArray(Json::decode($data['job']));
    }

    private function deleteJob(int $id) : void
    {
        $this->dbConnection->delete('job_queue', ['id' => $id]);
    }
}
