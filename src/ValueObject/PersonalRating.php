<?php declare(strict_types=1);

namespace Movary\ValueObject;

use RuntimeException;

class PersonalRating
{
    private const array ALLOWED_RATINGS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    private function __construct(private readonly int $rating)
    {
        if (in_array($this->rating, self::ALLOWED_RATINGS, true) === false) {
            throw new RuntimeException('Invalid rating: ' . $this->rating);
        }
    }

    public static function create(int $rating) : self
    {
        return new self($rating);
    }

    public function __toString() : string
    {
        return (string)$this->rating;
    }

    public function asInt() : int
    {
        return $this->rating;
    }

    public function isEqual(PersonalRating $personalRating) : bool
    {
        return $this->asInt() === $personalRating->asInt();
    }
}
