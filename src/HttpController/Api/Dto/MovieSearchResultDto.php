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
    ) : self {
        return new self(
            $tmdbId,
            $title,
            $overview,
            $releaseDate,
            $originalLanguage,
            $tmdbPosterPath,
            $movaryId,
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
        ];
    }

    public function withMovaryId(int $id) : self
    {
        return MovieSearchResultDto::create(
            $this->tmdbId,
            $this->title,
            $this->overview,
            $this->releaseDate,
            $this->originalLanguage,
            $this->tmdbPosterPath,
            $id,
        );
    }
}
