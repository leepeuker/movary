<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\Watched;

use Movary\Api\Trakt\ValueObject\TraktMovie;
use Movary\ValueObject\DateTime;

class Dto
{
    private function __construct(
        private readonly TraktMovie $movie,
        private readonly DateTime $lastUpdated,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            TraktMovie::createFromArray($data['movie']),
            DateTime::createFromString($data['last_updated_at']),
        );
    }

    public function getLastUpdated() : DateTime
    {
        return $this->lastUpdated;
    }

    public function getMovie() : TraktMovie
    {
        return $this->movie;
    }
}
