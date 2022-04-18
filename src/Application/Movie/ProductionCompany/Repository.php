<?php declare(strict_types=1);

namespace Movary\Application\Movie\ProductionCompany;

use Doctrine\DBAL\Connection;

class Repository
{
    private Connection $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function create(int $movieId, int $genreId, int $position) : void
    {
        $this->dbConnection->insert(
            'movie_production_company',
            [
                'movie_id' => $movieId,
                'company_id' => $genreId,
                'position' => $position,
            ]
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_production_company', ['movie_id' => $movieId]);
    }
}
