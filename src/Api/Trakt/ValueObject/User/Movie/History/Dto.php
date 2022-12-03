<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\History;

use Movary\Api\Trakt\ValueObject\Movie;
use Movary\ValueObject\DateTime;

class Dto
{
    private function __construct(
        private readonly \Movary\Api\Trakt\ValueObject\TraktMovie $movie,
        private readonly DateTime $watchedAt
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            \Movary\Api\Trakt\ValueObject\TraktMovie::createFromArray($data['movie']),
            DateTime::createFromString($data['watched_at']),
        );
    }

    public function getMovie() : \Movary\Api\Trakt\ValueObject\TraktMovie
    {
        return $this->movie;
    }

    public function getWatchedAt() : DateTime
    {
        return $this->watchedAt;
    }
}
