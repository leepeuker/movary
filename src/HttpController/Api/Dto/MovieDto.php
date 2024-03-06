<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;

class MovieDto implements JsonSerializable
{
    public function __construct(
        private readonly int $id,
        private readonly string $title,
        private readonly int $tmdbId,
        private readonly DateTime $createdAt,
        private readonly ?TraktId $traktId = null,
        private readonly ?string $imdbId = null,
        private readonly ?string $letterboxdId = null,
        private readonly ?string $tagline = null,
        private readonly ?string $overview = null,
        private readonly ?string $originalLanguage = null,
        private readonly ?int $runtime = null,
        private readonly ?Date $releaseDate = null,
        private readonly ?string $posterPath = null,
        private readonly ?float $tmdbVoteAverage = null,
        private readonly ?int $tmdbVoteCount = null,
        private readonly ?float $imdbRatingAverage = null,
        private readonly ?int $imdbRatingCount = null,
        private readonly ?DateTime $updatedAt = null,
        private readonly ?int $userRating = null,
    ) {
    }

    public static function create(
        int $id,
        string $title,
        int $tmdbId,
        DateTime $createdAt,
        ?TraktId $traktId = null,
        ?string $imdbId = null,
        ?string $letterboxdId = null,
        ?string $tagline = null,
        ?string $overview = null,
        ?string $originalLanguage = null,
        ?int $runtime = null,
        ?Date $releaseDate = null,
        ?string $posterPath = null,
        ?float $tmdbVoteAverage = null,
        ?int $tmdbVoteCount = null,
        ?float $imdbRatingAverage = null,
        ?int $imdbRatingCount = null,
        ?DateTime $updatedAt = null,
        ?int $userRating = null,
    ) : self {
        return new self(
            $id,
            $title,
            $tmdbId,
            $createdAt,
            $traktId,
            $imdbId,
            $letterboxdId,
            $tagline,
            $overview,
            $originalLanguage,
            $runtime,
            $releaseDate,
            $posterPath,
            $tmdbVoteAverage,
            $tmdbVoteCount,
            $imdbRatingAverage,
            $imdbRatingCount,
            $updatedAt,
            $userRating,
        );
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function jsonSerialize() : array
    {
        return [
            'title' => $this->title,
            'releaseDate' => $this->releaseDate,
            'tagline' => $this->tagline,
            'overview' => $this->overview,
            'originalLanguage' => $this->originalLanguage,
            'runtime' => $this->runtime,
            'posterPath' => $this->posterPath,
            'ids' => [
                'movary' => $this->id,
                'tmdb' => $this->tmdbId,
                'trakt' => $this->traktId,
                'imdb' => $this->imdbId,
                'letterboxd' => $this->letterboxdId,
            ],
            'userRating' => $this->userRating,
            'externalRatings' => [
                'tmdb' => [
                    'average' => $this->tmdbVoteAverage,
                    'count' => $this->tmdbVoteCount,
                ],
                'imdb' => [
                    'average' => $this->imdbRatingAverage,
                    'count' => $this->imdbRatingCount,
                ],
            ],
            'updatedAt' => $this->updatedAt,
            'createdAt' => $this->createdAt,
        ];
    }
}
