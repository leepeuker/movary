<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;

class PlexItem
{
    public function __construct(
        private readonly int $itemId,
        private readonly string $type,
        private readonly string $title,
        private readonly float $userRating,
        private readonly ?int $tmdbId,
        private readonly ?string $imdbId,
        private readonly ?string $lastViewedTimestamp
    ){
    }

    public static function createPlexItem(int $itemId, string $title, string $type, ?float $userRating = null, ?int $tmdbId = null, ?string $imdbId = null, ?string $lastViewedTimestamp = null) : self
    {
        return new self($itemId, $title, $type, $userRating, $tmdbId, $imdbId, $lastViewedTimestamp);
    }

    public function getPlexItemId() : int
    {
        return $this->itemId;
    }

    public function getTmdbId() : ?int
    {
        return $this->tmdbId;
    }
    
    public function getTitle() : ?string
    {
        return $this->title;
    }

    public function getUserRating() : ?float
    {
        return $this->userRating;
    }

    public function getLastViewedAt() : ?Date
    {
        $dateTime = DateTime::createFromFormatAndTimestamp('U', $this->lastViewedTimestamp);
        return Date::createFromDateTime($dateTime);
    }
}