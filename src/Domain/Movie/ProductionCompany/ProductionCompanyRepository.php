<?php declare(strict_types=1);

namespace Movary\Domain\Movie\ProductionCompany;

use Doctrine\DBAL\Connection;

class ProductionCompanyRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function create(int $movieId, int $genreId, int $position) : void
    {
        $this->dbConnection->insert(
            'movie_production_company',
            [
                'movie_id' => $movieId,
                'company_id' => $genreId,
                'position' => $position,
            ],
        );
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->dbConnection->delete('movie_production_company', ['movie_id' => $movieId]);
    }
}
