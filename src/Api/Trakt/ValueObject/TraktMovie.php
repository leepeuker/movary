<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject;

use Movary\ValueObject\Year;

class TraktMovie
{
    private function __construct(
        private readonly string $title,
        private readonly Year $year,
        private readonly TraktId $traktId,
        private readonly string $imdbId,
        private readonly int $tmdbId,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['title'],
            Year::createFromInt($data['year']),
            TraktId::createFromInt($data['ids']['trakt']),
            $data['ids']['imdb'],
            $data['ids']['tmdb'],
        );
    }

    public function getImdbId() : string
    {
        return $this->imdbId;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }

    public function getTraktId() : TraktId
    {
        return $this->traktId;
    }

    public function getYear() : Year
    {
        return $this->year;
    }
}
