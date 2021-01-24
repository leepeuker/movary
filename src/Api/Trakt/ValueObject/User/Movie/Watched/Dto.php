<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\Movie;
use Movary\ValueObject\DateTime;

class Dto
{
    private DateTime $lastUpdated;

    private DateTime $lastWatched;

    private Movie\Dto $movie;

    private int $plays;

    private function __construct(Movie\Dto $movie, int $plays, DateTime $lastWatched, DateTime $lastUpdated)
    {
        $this->movie = $movie;
        $this->plays = $plays;
        $this->lastWatched = $lastWatched;
        $this->lastUpdated = $lastUpdated;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            Movie\Dto::createFromArray($data['movie']),
            $data['plays'],
            DateTime::createFromStringAndTimeZone($data['last_watched_at'], 'GMT'),
            DateTime::createFromStringAndTimeZone($data['last_watched_at'], 'GMT'),
        );
    }

    public function getLastUpdated() : DateTime
    {
        return $this->lastUpdated;
    }

    public function getLastWatched() : DateTime
    {
        return $this->lastWatched;
    }

    public function getMovie() : Movie\Dto
    {
        return $this->movie;
    }

    public function getPlays() : int
    {
        return $this->plays;
    }
}
