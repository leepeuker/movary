<?php declare(strict_types=1);

namespace Movary\Api\Imdb\ValueObject;

class ImdbRating
{
    private function __construct(private readonly float $rating, private readonly int $votesCount)
    {
    }

    public static function create(float $rating, int $votesCount) : self
    {
        return new self($rating, $votesCount);
    }

    public function getRating() : float
    {
        return $this->rating;
    }

    public function getVotesCount() : int
    {
        return $this->votesCount;
    }
}
