<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\Movie;

use Movary\ValueObject\Year;

class Dto
{
    private string $imdbId;

    private string $title;

    private int $tmdbId;

    private TraktId $traktId;

    private Year $year;

    private function __construct(string $title, Year $year, TraktId $traktId, string $imdbId, int $tmdbId)
    {
        $this->title = $title;
        $this->year = $year;
        $this->traktId = $traktId;
        $this->imdbId = $imdbId;
        $this->tmdbId = $tmdbId;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['title'], Year::createFromInt($data['year']), TraktId::createFromInt($data['ids']['trakt']), $data['ids']['imdb'], $data['ids']['tmdb'],
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
