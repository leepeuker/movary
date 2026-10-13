<?php declare(strict_types=1);

namespace Movary\JobQueue;

use InvalidArgumentException;

class TmdbImageCacheJobParameters
{
    public const string IMAGE_TYPE_ALL = 'all';

    public const string IMAGE_TYPE_MOVIES = 'movies';

    public const string IMAGE_TYPE_PERSONS = 'persons';

    private const array IMAGE_TYPES = [
        self::IMAGE_TYPE_ALL,
        self::IMAGE_TYPE_MOVIES,
        self::IMAGE_TYPE_PERSONS,
    ];

    private function __construct(
        private readonly string $imageType,
        private readonly bool $forceRefresh,
        private readonly array $movieIds,
        private readonly array $personIds,
    ) {
    }

    public static function create(
        string $imageType,
        bool $forceRefresh,
        array $movieIds = [],
        array $personIds = [],
    ) : self {
        if (in_array($imageType, self::IMAGE_TYPES, true) === false) {
            throw new InvalidArgumentException('Image type is invalid.');
        }
        foreach ([...$movieIds, ...$personIds] as $id) {
            if (is_int($id) === false || $id < 1) {
                throw new InvalidArgumentException('IDs must contain only positive integers.');
            }
        }

        return new self(
            $imageType,
            $forceRefresh,
            array_values(array_unique($movieIds)),
            array_values(array_unique($personIds)),
        );
    }

    public function getImageType() : string
    {
        return $this->imageType;
    }

    public function isForceRefresh() : bool
    {
        return $this->forceRefresh;
    }

    public function getMovieIds() : array
    {
        return $this->movieIds;
    }

    public function getPersonIds() : array
    {
        return $this->personIds;
    }

    public function isFullRefresh() : bool
    {
        return $this->movieIds === [] && $this->personIds === [];
    }
}
