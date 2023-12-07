<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\DateTime;

class TmdbMovie
{
    private function __construct(
        private readonly int $id,
        private readonly string $title,
        private readonly string $originalLanguage,
        private readonly ?string $tagline,
        private readonly ?string $overview,
        private readonly ?DateTime $releaseDate,
        private readonly ?int $runtime,
        private readonly float $voteAverage,
        private readonly int $voteCount,
        private readonly TmdbGenreList $genres,
        private readonly TmdbProductionCompanyList $productionCompanies,
        private readonly ?string $posterPath,
        private readonly ?string $backdropPath,
        private readonly ?string $imdbId,
        private readonly TmdbCredits $credits,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['title'],
            $data['original_language'],
            empty($data['tagline']) === true ? null : $data['tagline'],
            empty($data['overview']) === true ? null : $data['overview'],
            empty($data['release_date']) === false ? DateTime::createFromString($data['release_date']) : null,
            $data['runtime'],
            $data['vote_average'],
            $data['vote_count'],
            TmdbGenreList::createFromArray($data['genres']),
            TmdbProductionCompanyList::createFromArray($data['production_companies']),
            $data['poster_path'],
            $data['backdrop_path'],
            empty($data['imdb_id']) === true ? null : $data['imdb_id'],
            empty($data['credits']) === true ? TmdbCredits::create() : TmdbCredits::createFromArray($data['credits']),
        );
    }

    public function getBackdropPath() : ?string
    {
        return $this->backdropPath;
    }

    public function getCredits() : TmdbCredits
    {
        return $this->credits;
    }

    public function getGenres() : TmdbGenreList
    {
        return $this->genres;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getImdbId() : ?string
    {
        return $this->imdbId;
    }

    public function getOriginalLanguage() : string
    {
        return $this->originalLanguage;
    }

    public function getOverview() : ?string
    {
        return $this->overview;
    }

    public function getPosterPath() : ?string
    {
        return $this->posterPath;
    }

    public function getProductionCompanies() : TmdbProductionCompanyList
    {
        return $this->productionCompanies;
    }

    public function getReleaseDate() : ?DateTime
    {
        return $this->releaseDate;
    }

    public function getRuntime() : ?int
    {
        return $this->runtime;
    }

    public function getTagline() : ?string
    {
        return $this->tagline;
    }

    public function getTitle() : string
    {
        return $this->title;
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
