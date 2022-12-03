<?php declare(strict_types=1);

namespace Movary\Domain\Movie\ProductionCompany;

class ProductionCompanyApi
{
    public function __construct(private readonly ProductionCompanyRepository $repository)
    {
    }

    public function create(int $movieId, int $genreId, int $position) : void
    {
        $this->repository->create($movieId, $genreId, $position);
    }

    public function deleteByMovieId(int $movieId) : void
    {
        $this->repository->deleteByMovieId($movieId);
    }
}
