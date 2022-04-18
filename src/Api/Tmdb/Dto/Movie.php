<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\DateTime;

class Movie
{
    private GenreList $genres;

    private int $id;

    private string $originalLanguage;

    private string $overview;

    private ProductionCompanyList $productionCompanies;

    private DateTime $releaseDate;

    private int $runtime;

    private float $voteAverage;

    private int $voteCount;

    private function __construct(
        int $id,
        string $originalLanguage,
        string $overview,
        DateTime $releaseDate,
        int $runtime,
        float $voteAverage,
        int $voteCount,
        GenreList $genres,
        ProductionCompanyList $productionCompanies
    ) {
        $this->id = $id;
        $this->originalLanguage = $originalLanguage;
        $this->overview = $overview;
        $this->releaseDate = $releaseDate;
        $this->runtime = $runtime;
        $this->voteAverage = $voteAverage;
        $this->voteCount = $voteCount;
        $this->genres = $genres;
        $this->productionCompanies = $productionCompanies;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['original_language'],
            $data['overview'],
            DateTime::createFromString($data['release_date']),
            $data['runtime'],
            $data['vote_average'],
            $data['vote_count'],
            GenreList::createFromArray($data['genres']),
            ProductionCompanyList::createFromArray($data['production_companies']),
        );
    }

    public function getGenres() : GenreList
    {
        return $this->genres;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getOriginalLanguage() : string
    {
        return $this->originalLanguage;
    }

    public function getOverview() : string
    {
        return $this->overview;
    }

    public function getProductionCompanies() : ProductionCompanyList
    {
        return $this->productionCompanies;
    }

    public function getReleaseDate() : DateTime
    {
        return $this->releaseDate;
    }

    public function getRuntime() : int
    {
        return $this->runtime;
    }

    public function getVoteAverage() : float
    {
        return $this->voteAverage;
    }

    public function getVoteCount() : int
    {
        return $this->voteCount;
    }
}
