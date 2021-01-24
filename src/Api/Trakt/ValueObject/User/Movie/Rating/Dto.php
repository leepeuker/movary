<?php declare(strict_types=1);

namespace Movary\Api\Trakt\ValueObject\User\Movie\Rating;

use Movary\Api\Trakt\ValueObject\Movie;
use Movary\ValueObject\DateTime;

class Dto
{
    private Movie\Dto $movie;

    private DateTime $ratedAt;

    private int $rating;

    private function __construct(Movie\Dto $movie, int $rating, DateTime $ratedAt)
    {
        $this->movie = $movie;
        $this->rating = $rating;
        $this->ratedAt = $ratedAt;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            Movie\Dto::createFromArray($data['movie']), $data['rating'], DateTime::createFromString($data['rated_at']),
        );
    }

    public function getMovie() : Movie\Dto
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
