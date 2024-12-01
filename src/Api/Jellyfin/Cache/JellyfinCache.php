<?php

namespace Movary\Api\Jellyfin\Cache;

use Doctrine\DBAL\Connection;
use Movary\Api\Jellyfin\Dto\JellyfinMovieDtoList;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\JellyfinClient;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;

class JellyfinCache
{
    private const array DEFAULT_HTTP_HEADERS = [
        'Recursive' => 'true',
        'IncludeItemTypes' => 'Movie',
        'hasTmdbId' => 'true',
        'filters' => 'IsNotFolder',
        'fields' => 'ProviderIds',
        'limit' => 1000,
    ];

    public function __construct(
        private readonly Connection $dbConnection,
        private readonly JellyfinCacheRepository $repository,
        private readonly UserApi $userApi,
        private readonly JellyfinClient $client,
        private readonly JellyfinCacheMapper $jellyfinMapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function delete(int $userId) : void
    {
        $this->repository->delete($userId);
    }

    public function fetchJellyfinMoviesByTmdbIds(int $userId, array $tmdbIds) : JellyfinMovieDtoList
    {
        $this->loadFromJellyfin($userId);

        return $this->repository->fetchJellyfinMoviesByTmdbIds($userId, $tmdbIds);
    }

    public function fetchJellyfinPlayedMovies(int $userId) : JellyfinMovieDtoList
    {
        $this->loadFromJellyfin($userId);

        return $this->repository->fetchJellyfinPlayedMovies($userId);
    }

    public function loadFromJellyfin(int $userId) : void
    {
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($userId);

        if ($jellyfinAuthentication === null) {
            throw JellyfinInvalidAuthentication::create();
        }

        $relativeUrl = RelativeUrl::create("/Users/{$jellyfinAuthentication->getUserId()}/Items");
        $url = $jellyfinAuthentication->getServerUrl()->appendRelativeUrl($relativeUrl);

        $jellyfinPages = $this->client->getPaginated($url, self::DEFAULT_HTTP_HEADERS, jellyfinAccessToken: $jellyfinAuthentication->getAccessToken());
        $cachedJellyfinMovies = $this->repository->fetchJellyfinMoviesByUserId($userId);

        $this->dbConnection->beginTransaction();

        $existingJellyfinItemIds = [];
        foreach ($this->jellyfinMapper->map($jellyfinAuthentication->getUserId(), $jellyfinPages) as $jellyfinMovieDto) {
            $jellyfinItemId = $jellyfinMovieDto->getJellyfinItemId();

            $cachedMovie = $cachedJellyfinMovies->getByItemId($jellyfinItemId);
            $existingJellyfinItemIds[$jellyfinItemId] = true;

            if ($cachedMovie !== null && $cachedMovie->isEqual($jellyfinMovieDto) === true) {
                $this->logger->debug('Jellyfin cache: Skipped updating unchanged movie', [
                    'userId' => $userId,
                    'jellyfinItemId' => $jellyfinItemId
                ]);

                continue;
            }

            if ($cachedMovie !== null) {
                $this->repository->updateCacheEntry($userId, $jellyfinMovieDto);

                $this->logger->info('Jellyfin cache: Updated movie', [
                    'userId' => $userId,
                    'jellyfinItemId' => $jellyfinItemId,
                    'watched' => $jellyfinMovieDto->getWatched()
                ]);

                continue;
            }

            $this->repository->addCacheEntry($userId, $jellyfinMovieDto);

            $this->logger->info('Jellyfin cache: Added movie', [
                'userId' => $userId,
                'jellyfinItemId' => $jellyfinItemId,
                'watched' => $jellyfinMovieDto->getWatched()
            ]);
        }

        $this->removeOutdatedCache($userId, $cachedJellyfinMovies, $existingJellyfinItemIds);

        $this->dbConnection->commit();
    }

    private function removeOutdatedCache(int $userId, JellyfinMovieDtoList $latestCachedJellyfinMovies, array $oldCachedJellyfinItemIds) : void
    {
        foreach ($latestCachedJellyfinMovies as $latestCachedJellyfinMovie) {
            $cachedJellyfinItemId = $latestCachedJellyfinMovie->getJellyfinItemId();

            if (isset($oldCachedJellyfinItemIds[$cachedJellyfinItemId]) === true) {
                continue;
            }

            $this->repository->deleteCacheEntry($userId, $cachedJellyfinItemId);

            $this->logger->info('Jellyfin cache: Removed movie', ['userId' => $userId, 'jellyfinItemId' => $cachedJellyfinItemId]);
        }
    }
}
