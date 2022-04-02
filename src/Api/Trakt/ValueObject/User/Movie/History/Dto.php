<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\History;

use Movary\Api\Trakt\ValueObject\Movie;
use Movary\ValueObject\DateTime;

class Dto
{
    private Movie\Dto $movie;

    private DateTime $watchedAt;

    private function __construct(Movie\Dto $movie, DateTime $watchedAt)
    {
        $this->movie = $movie;
        $this->watchedAt = $watchedAt;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            Movie\Dto::createFromArray($data['movie']),
            DateTime::createFromStringAndTimeZone($data['watched_at'], 'GMT'),
        );
    }

    public function getMovie() : Movie\Dto
    {
        return $this->movie;
    }

    public function getWatchedAt() : DateTime
    {
        return $this->watchedAt;
    }
}
