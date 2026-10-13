<?php declare(strict_types=1);

namespace Movary\JobQueue;

use InvalidArgumentException;

class TmdbImageCacheJobParameters
{
    private function __construct(
        private readonly array $movieIds,
        private readonly array $personIds,
    ) {
    }

    public static function create(array $movieIds, array $personIds) : self
    {
        if ($movieIds === [] && $personIds === []) {
            throw new InvalidArgumentException('At least one movie or person ID is required.');
        }
        foreach ([...$movieIds, ...$personIds] as $id) {
            if (is_int($id) === false || $id < 1) {
                throw new InvalidArgumentException('IDs must contain only positive integers.');
            }
        }

        return new self(
            array_values(array_unique($movieIds)),
            array_values(array_unique($personIds)),
        );
    }

    public function getMovieIds() : array
    {
        return $this->movieIds;
    }

    public function getPersonIds() : array
    {
        return $this->personIds;
    }
}
