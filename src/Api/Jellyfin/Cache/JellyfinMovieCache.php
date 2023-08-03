<?php

namespace Movary\Api\Jellyfin\Cache;

use Doctrine\DBAL\Connection;
use Movary\Api\Jellyfin\Dto\JellyfinMovieDtoList;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\JellyfinClient;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\RelativeUrl;
use Psr\Log\LoggerInterface;

class JellyfinMovieCache
{
    private const DEFAULT_HTTP_HEADERS = [
        'Recursive' => 'true',
        'IncludeItemTypes' => 'Movie',
        'hasTmdbId' => 'true',
        'filters' => 'IsNotFolder',
        'fields' => 'ProviderIds',
        'limit' => 1000,
    ];

    public function __construct(
        private readonly Connection $dbConnection,
        private readonly UserApi $userApi,
        private readonly JellyfinClient $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchJellyfinMoviesByTmdbIds(int $userId, array $tmdbIds) : JellyfinMovieDtoList
    {
        $this->loadFromJellyfin($userId);

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

    private function addCacheEntry(int $userId, string $jellyfinItemId, int $tmdbId, bool $watched, ?Date $lastPlayedDate) : void
    {
        $this->dbConnection->insert(
            'user_jellyfin_cache',
            [
                'movary_user_id' => $userId,
                'jellyfin_item_id' => $jellyfinItemId,
                'tmdb_id' => $tmdbId,
                'watched' => (int)$watched,
                'last_watch_date' => $lastPlayedDate === null ? null : (string)$lastPlayedDate,
                'created_at' => (string)DateTime::create(),
            ],
        );
    }

    private function deleteCacheEntry(int $userId, string $jellyfinItemId) : void
    {
        $this->dbConnection->delete(
            'user_jellyfin_cache',
            [
                'movary_user_id' => $userId,
                'jellyfin_item_id' => $jellyfinItemId,
            ],
        );
    }

    private function extractTmdbId(array $jellyfinMovie) : ?int
    {
        foreach ($jellyfinMovie['ProviderIds'] ?? [] as $provider => $id) {
            if ($provider === 'Tmdb') {
                return (int)$id;
            }
        }

        return null;
    }

    private function fetchJellyfinMoviesByUserId(int $userId) : JellyfinMovieDtoList
    {
        $result = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM user_jellyfin_cache JOIN user u on id = movary_user_id WHERE movary_user_id = ?',
            [$userId],
        );

        return JellyfinMovieDtoList::createFromArray($result);
    }

    private function loadFromJellyfin(int $userId) : void
    {
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($userId);

        if ($jellyfinAuthentication === null) {
            throw JellyfinInvalidAuthentication::create();
        }

        $relativeUrl = RelativeUrl::create("/Users/{$jellyfinAuthentication->getUserId()}/Items");
        $url = $jellyfinAuthentication->getServerUrl()->appendRelativeUrl($relativeUrl);

        $jellyfinPages = $this->client->getPaginated($url, self::DEFAULT_HTTP_HEADERS, jellyfinAccessToken: $jellyfinAuthentication->getAccessToken());
        $cachedJellyfinMovies = $this->fetchJellyfinMoviesByUserId($userId);

        $this->dbConnection->beginTransaction();

        $existingJellyfinItemIds = [];
        foreach ($jellyfinPages as $jellyfinPage) {
            foreach ($jellyfinPage['Items'] as $jellyfinMovie) {
                $tmdbId = $this->extractTmdbId($jellyfinMovie);
                if ($tmdbId === null) {
                    continue;
                }

                $watched = $jellyfinMovie['UserData']['Played'];
                $lastPlayedDate = isset($jellyfinMovie['UserData']['LastPlayedDate']) === true ? Date::createFromString($jellyfinMovie['UserData']['LastPlayedDate']) : null;
                $jellyfinItemId = $jellyfinMovie['Id'];
                $existingJellyfinItemIds[$jellyfinItemId] = true;

                $cachedMovie = $cachedJellyfinMovies->getByItemId($jellyfinItemId);

                if ($cachedMovie !== null &&
                    $cachedMovie->getWatched() === $watched &&
                    $cachedMovie->getTmdbId() === $tmdbId &&
                    (string)$cachedMovie->getLastWatchDate() === (string)$lastPlayedDate) {
                    $this->logger->debug('Jellyfin cache: Skipped updating unchanged movie', ['userId' => $userId, 'jellyfinItemId' => $jellyfinItemId]);
                    continue;
                }

                $this->deleteCacheEntry($userId, $jellyfinItemId);
                $this->addCacheEntry($userId, $jellyfinItemId, $tmdbId, $watched, $lastPlayedDate);

                $this->logger->debug('Jellyfin cache: Updated movie', ['userId' => $userId, 'jellyfinItemId' => $jellyfinItemId, 'watched' => $watched]);
            }
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

            $this->deleteCacheEntry($userId, $cachedJellyfinItemId);

            $this->logger->debug('Jellyfin cache: Removed movie', ['userId' => $userId, 'jellyfinItemId' => $cachedJellyfinItemId]);
        }
    }
}
