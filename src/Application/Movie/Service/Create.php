<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;

class Create
{
    public function __construct(private readonly Repository $repository)
    {
    }

    public function create(
        string $title,
        int $tmdbId,
        ?string $tagline = null,
        ?string $overview = null,
        ?string $originalLanguage = null,
        ?Date $releaseDate = null,
        ?int $runtime = null,
        ?float $tmdbVoteAverage = null,
        ?int $tmdbVoteCount = null,
        ?string $tmdbPosterPath = null,
        ?TraktId $traktId = null,
        ?string $imdbId = null,
    ) : Entity {
        return $this->repository->create(
            $title,
            $tmdbId,
            $tagline,
            $overview,
            $originalLanguage,
            $releaseDate,
            $runtime,
            $tmdbVoteAverage,
            $tmdbVoteCount,
            $tmdbPosterPath,
            $traktId,
            $imdbId
        );
    }
}
