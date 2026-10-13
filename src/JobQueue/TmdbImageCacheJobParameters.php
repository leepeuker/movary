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
    ) {
    }

    public static function create(string $imageType, bool $forceRefresh) : self
    {
        if (in_array($imageType, self::IMAGE_TYPES, true) === false) {
            throw new InvalidArgumentException('Image type is invalid.');
        }

        return new self($imageType, $forceRefresh);
    }

    public function getImageType() : string
    {
        return $this->imageType;
    }

    public function isForceRefresh() : bool
    {
        return $this->forceRefresh;
    }
}
