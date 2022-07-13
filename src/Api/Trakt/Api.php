<?php declare(strict_types=1);

namespace Movary\Api\Trakt;

use Movary\Api\Trakt\Cache\User\Movie\Watched;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Api\Trakt\ValueObject\User;

class Api
{
    public function __construct(
        private readonly Client $client,
        private readonly string $username,
        private readonly Watched\Service $cacheWatchedService
    ) {
    }

    public function fetchUniqueCachedTraktIds(int $userId) : array
    {
        return $this->cacheWatchedService->fetchAllUniqueTraktIds($userId);
    }

    public function fetchUserMovieHistoryByMovieId(string $clientId, TraktId $traktId) : User\Movie\History\DtoList
    {
        $responseData = $this->client->get($clientId, sprintf('/users/%s/history/movies/%d', $this->username, $traktId->asInt()));

        return User\Movie\History\DtoList::createFromArray($responseData);
    }

    public function fetchUserMoviesRatings(string $clientId,) : User\Movie\Rating\DtoList
    {
        $responseData = $this->client->get($clientId, sprintf('/users/%s/ratings/movies', $this->username));

        return User\Movie\Rating\DtoList::createFromArray($responseData);
    }

    public function fetchUserMoviesWatched(string $clientId) : User\Movie\Watched\DtoList
    {
        $responseData = $this->client->get($clientId, sprintf('/users/%s/watched/movies', $this->username));

        return User\Movie\Watched\DtoList::createFromArray($responseData);
    }

    public function removeWatchCacheByTraktId(int $userId, TraktId $traktId) : void
    {
        $this->cacheWatchedService->remove($userId, $traktId);
    }
}
