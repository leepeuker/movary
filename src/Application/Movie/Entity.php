<?php declare(strict_types=1);

namespace Movary\Application\Movie;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\DateTime;

class Entity
{
    private int $id;

    private string $imdbId;

    private ?string $originalLanguage;

    private ?string $overview;

    private ?int $rating;

    private ?DateTime $releaseDate;

    private ?int $runtime;

    private string $title;

    private int $tmdbId;

    private ?float $tmdbVoteAverage;

    private ?int $tmdbVoteCount;

    private TraktId $traktId;

    private ?DateTime $updatedAtTmdb;

    private function __construct(
        int $id,
        string $title,
        ?int $rating,
        TraktId $traktId,
        string $imdbId,
        int $tmdbId,
        ?string $overview,
        ?string $originalLanguage,
        ?int $runtime,
        ?DateTime $releaseDate,
        ?float $tmdbVoteAverage,
        ?int $tmdbVoteCount,
        ?DateTime $updatedAtTmdb
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->rating = $rating;
        $this->traktId = $traktId;
        $this->imdbId = $imdbId;
        $this->tmdbId = $tmdbId;
        $this->overview = $overview;
        $this->originalLanguage = $originalLanguage;
        $this->runtime = $runtime;
        $this->releaseDate = $releaseDate;
        $this->tmdbVoteAverage = $tmdbVoteAverage;
        $this->tmdbVoteCount = $tmdbVoteCount;
        $this->updatedAtTmdb = $updatedAtTmdb;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            $data['title'],
            $data['rating'] === null ? null : (int)$data['rating'],
            TraktId::createFromString((string)$data['trakt_id']),
            $data['imdb_id'],
            (int)$data['tmdb_id'],
            $data['overview'],
            $data['original_language'],
            $data['runtime'] === null ? null : (int)$data['runtime'],
            $data['release_date'] === null ? null : DateTime::createFromString($data['release_date']),
            $data['tmdb_vote_average'] === null ? null : (float)$data['tmdb_vote_average'],
            $data['tmdb_vote_count'] === null ? null : (int)$data['tmdb_vote_count'],
            $data['updated_at_tmdb'] === null ? null : DateTime::createFromString($data['updated_at_tmdb']),
        );
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getImdbId() : string
    {
        return $this->imdbId;
    }

    public function getOriginalLanguage() : ?string
    {
        return $this->originalLanguage;
    }

    public function getOverview() : ?string
    {
        return $this->overview;
    }

    public function getRating() : ?int
    {
        return $this->rating;
    }

    public function getReleaseDate() : ?DateTime
    {
        return $this->releaseDate;
    }

    public function getRuntime() : ?int
    {
        return $this->runtime;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }

    public function getTmdbVoteAverage() : ?float
    {
        return $this->tmdbVoteAverage;
    }

    public function getTmdbVoteCount() : ?int
    {
        return $this->tmdbVoteCount;
    }

    public function getTraktId() : TraktId
    {
        return $this->traktId;
    }

    public function getUpdatedAtTmdb() : ?DateTime
    {
        return $this->updatedAtTmdb;
    }
}
