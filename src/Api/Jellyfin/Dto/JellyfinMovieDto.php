<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

class JellyfinMovieDto
{
    private function __construct(
        private readonly string $jellyfinUserId,
        private readonly string $jellyfinItemId,
        private readonly int $tmdbID,
        private readonly bool $watched,
    ) {
    }

    public static function create(string $jellyfinUserId, string $jellyfinItemId, int $tmdbId, bool $watched) : self
    {
        return new self($jellyfinUserId, $jellyfinItemId, $tmdbId, $watched);
    }

    public static function createFromArray(array $movieData) : self
    {
        return self::create(
            $movieData['jellyfin_user_id'],
            $movieData['jellyfin_item_id'],
            $movieData['tmdb_id'],
            (bool)$movieData['watched'],
        );
    }

    public function getJellyfinItemId() : string
    {
        return $this->jellyfinItemId;
    }

    public function getJellyfinUserId() : string
    {
        return $this->jellyfinUserId;
    }

    public function getWatched() : bool
    {
        return $this->watched;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbID;
    }
}
