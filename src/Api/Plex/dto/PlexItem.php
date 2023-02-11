<?php declare(strict_types=1);

namespace Movary\Api\Plex\Dto;

class PlexItem
{
    public function __construct(
        private readonly int $itemId,
        private readonly string $type,
        private readonly ?string $tmdbId,
        private readonly ?string $imdbId,
    ){
    }

    public static function createPlexItem(int $itemId,  ?string $type, ?string $tmdbId = null, ?string $imdbId = null) : self
    {
        return new self($itemId, $type, $tmdbId, $imdbId);
    }

    public function getPlexItemId() : int
    {
        return $this->itemId;
    }

    public function getTmdbId() : ?int
    {
        return $this->tmdbId;
    }
}