<?php declare(strict_types=1);

namespace Movary\Application\Movie;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\ValueObject\Year;

class Entity
{
    private int $id;

    private string $imdbId;

    private ?int $rating;

    private string $title;

    private int $tmdbId;

    private TraktId $traktId;

    private Year $year;

    private function __construct(int $id, string $title, Year $year, ?int $rating, TraktId $traktId, string $imdbId, int $tmdbId)
    {
        $this->id = $id;
        $this->title = $title;
        $this->year = $year;
        $this->rating = $rating;
        $this->traktId = $traktId;
        $this->imdbId = $imdbId;
        $this->tmdbId = $tmdbId;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            $data['title'],
            Year::createFromString($data['year']),
            (int)$data['rating'],
            TraktId::createFromString($data['trakt_id']),
            $data['imdb_id'],
            (int)$data['tmdb_id'],
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

    public function getRating() : ?int
    {
        return $this->rating;
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
