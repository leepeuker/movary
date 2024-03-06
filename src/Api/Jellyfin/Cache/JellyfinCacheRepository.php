<?php

namespace Movary\Api\Jellyfin\Cache;

use Doctrine\DBAL\Connection;
use Movary\Api\Jellyfin\Dto\JellyfinMovieDto;
use Movary\Api\Jellyfin\Dto\JellyfinMovieDtoList;
use Movary\ValueObject\DateTime;

class JellyfinCacheRepository
{
    public function __construct(
        private readonly Connection $dbConnection,
    ) {
    }

    public function addCacheEntry(int $userId, JellyfinMovieDto $jellyfinMovieDto) : void
    {
        $lastWatchDate = $jellyfinMovieDto->getLastWatchDate();

        $this->dbConnection->insert(
            'user_jellyfin_cache',
            [
                'movary_user_id' => $userId,
                'jellyfin_item_id' => $jellyfinMovieDto->getJellyfinItemId(),
                'tmdb_id' => $jellyfinMovieDto->getTmdbId(),
                'watched' => (int)$jellyfinMovieDto->getWatched(),
                'last_watch_date' => $lastWatchDate === null ? null : (string)$lastWatchDate,
                'created_at' => (string)DateTime::create(),
            ],
        );
    }

    public function delete(int $userId) : void
    {
        $this->dbConnection->delete('user_jellyfin_cache', ['movary_user_id' => $userId]);
    }

    public function deleteCacheEntry(int $userId, string $jellyfinItemId) : void
    {
        $this->dbConnection->delete(
            'user_jellyfin_cache',
            [
                'movary_user_id' => $userId,
                'jellyfin_item_id' => $jellyfinItemId,
            ],
        );
    }

    public function fetchJellyfinMoviesByTmdbIds(int $userId, array $tmdbIds) : JellyfinMovieDtoList
    {
        if (count($tmdbIds) === 0) {
            return JellyfinMovieDtoList::create();
        }

        $placeholders = trim(str_repeat('?, ', count($tmdbIds)), ', ');

        $result = $this->dbConnection->fetchAllAssociative(
            "SELECT * FROM user_jellyfin_cache JOIN user u on id = movary_user_id WHERE movary_user_id = ? AND tmdb_id IN ($placeholders)",
            [
                $userId,
                ...$tmdbIds
            ],
        );

        return JellyfinMovieDtoList::createFromArray($result);
    }

    public function fetchJellyfinMoviesByUserId(int $userId) : JellyfinMovieDtoList
    {
        $result = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM user_jellyfin_cache JOIN user u on id = movary_user_id WHERE movary_user_id = ?',
            [$userId],
        );

        return JellyfinMovieDtoList::createFromArray($result);
    }

    public function fetchJellyfinPlayedMovies(int $userId) : JellyfinMovieDtoList
    {
        $result = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM user_jellyfin_cache JOIN user u on id = movary_user_id WHERE movary_user_id = ? AND watched = 1 AND last_watch_date IS NOT NULL',
            [$userId],
        );

        return JellyfinMovieDtoList::createFromArray($result);
    }

    public function updateCacheEntry(int $userId, JellyfinMovieDto $jellyfinMovieDto) : void
    {
        $lastWatchDate = $jellyfinMovieDto->getLastWatchDate();

        $this->dbConnection->update(
            'user_jellyfin_cache',
            [
                'tmdb_id' => $jellyfinMovieDto->getTmdbId(),
                'watched' => (int)$jellyfinMovieDto->getWatched(),
                'last_watch_date' => $lastWatchDate === null ? null : (string)$lastWatchDate,
                'updated_at' => (string)DateTime::create(),
            ],
            [
                'movary_user_id' => $userId,
                'jellyfin_item_id' => $jellyfinMovieDto->getJellyfinItemId(),
            ],
        );
    }
}
