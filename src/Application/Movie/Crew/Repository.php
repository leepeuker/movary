<?php declare(strict_types=1);

namespace Movary\Application\Movie\Crew;

use Doctrine\DBAL\Connection;

class Repository
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function create(int $movieId, int $personId, string $job, string $department, int $position) : void
    {
        $this->dbConnection->insert(
            'movie_crew',
            [
                'movie_id' => $movieId,
                'person_id' => $personId,
                'job' => $job,
                'department' => $department,
                'position' => $position,
            ]
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_crew', ['movie_id' => $movieId]);
    }
}
