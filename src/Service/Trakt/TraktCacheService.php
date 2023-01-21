<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api;
use Movary\Api\Trakt\TraktApi;

class TraktCacheService
{
    public function __construct(
        private readonly TraktApi $traktApi,
        private readonly Api\Trakt\Cache\User\Movie\Watched\Service $traktApiWatchedMoviesCache,
        private readonly TraktCredentialsProvider $credentialsProvider,
    ) {
    }

    public function updateCache(int $userId) : void
    {
        $traktCredentials = $this->credentialsProvider->fetchTraktCredentialsByUserId($userId);

        foreach ($this->traktApi->fetchUserMoviesWatched($traktCredentials) as $traktWatchedMovie) {
            $this->traktApiWatchedMoviesCache->setLastUpdated($userId, $traktWatchedMovie->getMovie()->getTraktId(), $traktWatchedMovie->getLastUpdated());
        }
    }
}
