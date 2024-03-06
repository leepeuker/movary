<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin\Dto;

use Movary\ValueObject\Date;

class JellyfinMovieDto
{
    private function __construct(
        private readonly string $jellyfinUserId,
        private readonly string $jellyfinItemId,
        private readonly int $tmdbId,
        private readonly bool $watched,
        private readonly ?Date $lastWatchDate,
    ) {
    }

    public static function create(string $jellyfinUserId, string $jellyfinItemId, int $tmdbId, bool $watched, ?Date $lastWatchDate) : self
    {
        return new self($jellyfinUserId, $jellyfinItemId, $tmdbId, $watched, $lastWatchDate);
    }

    public static function createFromArray(array $movieData) : self
    {
        return self::create(
            $movieData['jellyfin_user_id'],
            $movieData['jellyfin_item_id'],
            $movieData['tmdb_id'],
            (bool)$movieData['watched'],
            isset($movieData['last_watch_date']) === true ? Date::createFromString($movieData['last_watch_date']) : null,
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

    public function getLastWatchDate() : ?Date
    {
        return $this->lastWatchDate;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }

    public function getWatched() : bool
    {
        return $this->watched;
    }

    public function isEqual(self $jellyfinMovieDto) : bool
    {
        return $this->watched === $jellyfinMovieDto->watched &&
            $this->jellyfinUserId === $jellyfinMovieDto->jellyfinUserId &&
            $this->tmdbId === $jellyfinMovieDto->tmdbId &&
            (string)$this->lastWatchDate === (string)$jellyfinMovieDto->lastWatchDate &&
            $this->jellyfinItemId === $jellyfinMovieDto->jellyfinItemId;
    }
}
