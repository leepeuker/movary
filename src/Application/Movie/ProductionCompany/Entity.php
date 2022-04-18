<?php declare(strict_types=1);

namespace Movary\Application\Movie\ProductionCompany;

class Entity
{
    private int $companyId;

    private int $movieId;

    private int $position;

    private function __construct(int $movieId, int $companyId, int $position)
    {
        $this->movieId = $movieId;
        $this->companyId = $companyId;
        $this->position = $position;
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
