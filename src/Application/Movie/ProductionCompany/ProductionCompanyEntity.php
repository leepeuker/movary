<?php declare(strict_types=1);

namespace Movary\Application\Movie\ProductionCompany;

class ProductionCompanyEntity
{
    private function __construct(
        private readonly int $movieId,
        private readonly int $companyId,
        private readonly int $position
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['company_id'],
            (int)$data['movie_id'],
            (int)$data['position'],
        );
    }

    public function getCompanyId() : int
    {
        return $this->companyId;
    }

    public function getMovieId() : int
    {
        return $this->movieId;
    }

    public function getPosition() : int
    {
        return $this->position;
    }
}
