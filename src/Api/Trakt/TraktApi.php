<?php declare(strict_types=1);

namespace Movary\Api\Trakt;

use Movary\Api\Trakt\Cache\User\Movie\Watched;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Api\Trakt\ValueObject\User;
use RuntimeException;

class TraktApi
{
    public function __construct(
        private readonly TraktClient $client,
        private readonly Watched\Service $cacheWatchedService,
    ) {
    }

    public function fetchUniqueCachedTraktIds(int $userId) : array
    {
        return $this->cacheWatchedService->fetchAllUniqueTraktIds($userId);
    }

    public function fetchUserMovieHistoryByMovieId(string $clientId, string $username, TraktId $traktId) : User\Movie\History\DtoList
    {
        $responseData = $this->client->get($clientId, sprintf('/users/%s/history/movies/%d', $username, $traktId->asInt()));

        return User\Movie\History\DtoList::createFromArray($responseData);
    }

    public function fetchUserMoviesRatings(string $clientId, string $username) : User\Movie\Rating\DtoList
    {
        $responseData = $this->client->get($clientId, sprintf('/users/%s/ratings/movies', $username));

        return User\Movie\Rating\DtoList::createFromArray($responseData);
    }

    public function fetchUserMoviesWatched(string $clientId, string $username) : User\Movie\Watched\DtoList
    {
        $responseData = $this->client->get($clientId, sprintf('/users/%s/watched/movies', $username));

        return User\Movie\Watched\DtoList::createFromArray($responseData);
    }

    public function removeWatchCacheByTraktId(int $userId, TraktId $traktId) : void
    {
        $this->cacheWatchedService->remove($userId, $traktId);
    }

    public function verifyCredentials(string $clientId, string $username) : bool
    {
        $response = $this->client->getResponse($clientId, sprintf('/users/%s/watched/movies', $username));

        if ($response->getStatusCode() === 200) {
            return true;
        }

        if ($response->getStatusCode() === 403 || $response->getStatusCode() === 404) {
            return false;
        }

        throw new RuntimeException('Trakt api error. Response status code: ' . $response->getStatusCode());
    }
}
