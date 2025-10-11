<?php declare(strict_types=1);

namespace Movary\HttpController\Api\Dto;

use JsonSerializable;
use Movary\ValueObject\Date;

class MovieSearchResultDto implements JsonSerializable
{
    public function __construct(
        private readonly int $tmdbId,
        private readonly string $title,
        private readonly ?string $overview,
        private readonly ?Date $releaseDate,
        private readonly ?string $originalLanguage,
        private readonly ?string $tmdbPosterPath,
        private readonly ?int $movaryId,
        private readonly bool $isWatched,
        private readonly bool $isOnWatchlist,
    ) {
    }

    public static function create(
        int $tmdbId,
        string $title,
        ?string $overview = null,
        ?Date $releaseDate = null,
        ?string $originalLanguage = null,
        ?string $tmdbPosterPath = null,
        ?int $movaryId = null,
        bool $isWatched = false,
        bool $isOnWatchlist = false,
    ) : self {
        return new self(
            $tmdbId,
            $title,
            $overview,
            $releaseDate,
            $originalLanguage,
            $tmdbPosterPath,
            $movaryId,
            $isWatched,
            $isOnWatchlist,
        );
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }

    public function jsonSerialize() : array
    {
        return [
            'title' => $this->title,
            'releaseDate' => $this->releaseDate,
            'overview' => $this->overview,
            'originalLanguage' => $this->originalLanguage,
            'tmdbPosterPath' => $this->tmdbPosterPath,
            'ids' => [
                'movary' => $this->movaryId,
                'tmdb' => $this->tmdbId,
            ],
            'isWatched' => $this->isWatched,
            'isOnWatchlist' => $this->isOnWatchlist,
        ];
    }

    public function withIsOnWatchlist(bool $isOnWatchlist) : self
    {
        return MovieSearchResultDto::create(
            $this->tmdbId,
            $this->title,
            $this->overview,
            $this->releaseDate,
            $this->originalLanguage,
            $this->tmdbPosterPath,
            $this->movaryId,
            $this->isWatched,
            $isOnWatchlist,
        );
    }

    public function withIsWatched(bool $isWatched) : self
    {
        return MovieSearchResultDto::create(
            $this->tmdbId,
            $this->title,
            $this->overview,
            $this->releaseDate,
            $this->originalLanguage,
            $this->tmdbPosterPath,
            $this->movaryId,
            $isWatched,
            $this->isOnWatchlist,
        );
    }

    public function withMovaryId(int $movaryId) : self
    {
        return MovieSearchResultDto::create(
            $this->tmdbId,
            $this->title,
            $this->overview,
            $this->releaseDate,
            $this->originalLanguage,
            $this->tmdbPosterPath,
            $movaryId,
            $this->isWatched,
            $this->isOnWatchlist,
        );
    }
}
