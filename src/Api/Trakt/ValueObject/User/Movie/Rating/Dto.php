<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\Rating;

use Movary\Api\Trakt\ValueObject\TraktMovie;
use Movary\ValueObject\DateTime;

class Dto
{
    private function __construct(
        private readonly TraktMovie $movie,
        private readonly int $rating,
        private readonly DateTime $ratedAt,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            TraktMovie::createFromArray($data['movie']),
            $data['rating'],
            DateTime::createFromStringAndTimeZone($data['rated_at'], 'GMT'),

        );
    }

    public function getMovie() : TraktMovie
    {
        return $this->movie;
    }

    public function getRatedAt() : DateTime
    {
        return $this->ratedAt;
    }

    public function getRating() : int
    {
        return $this->rating;
    }
}
