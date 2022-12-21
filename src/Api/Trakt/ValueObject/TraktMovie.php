<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject;

class TraktMovie
{
    private function __construct(
        private readonly TraktId $traktId,
        private readonly int $tmdbId,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            TraktId::createFromInt($data['ids']['trakt']),
            $data['ids']['tmdb'],
        );
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }

    public function getTraktId() : TraktId
    {
        return $this->traktId;
    }
}
