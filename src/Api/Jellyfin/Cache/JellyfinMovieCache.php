<?php

namespace Movary\Api\Jellyfin\Cache;

use Doctrine\DBAL\Connection;
use Movary\Api\Jellyfin\Dto\JellyfinMovieDtoList;
use Movary\Api\Jellyfin\JellyfinClient;
use Movary\Domain\User\UserApi;
use Movary\ValueObject\RelativeUrl;

class JellyfinMovieCache
{
    public function __construct(
        private readonly Connection $dbConnection,
        private readonly UserApi $userApi,
        private readonly JellyfinClient $client,
    ) {
    }

    public function fetchJellyfinMoviesByTmdbId(int $userId, int $tmdbId) : JellyfinMovieDtoList
    {
        $this->loadFromJellyfin($userId);

        $result = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM user_jellyfin_cache JOIN user u on id = movary_user_id WHERE movary_user_id = ? AND tmdb_id = ?',
            [$userId, $tmdbId],
        );

        return JellyfinMovieDtoList::createFromArray($result);
    }

    public function loadFromJellyfin(int $userId) : void
    {
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($userId);

        $jellyfinPages = $this->client->getPaginated(
            $jellyfinAuthentication
                ->getServerUrl()
                ->appendRelativeUrl(
                    RelativeUrl::create("/Users/{$jellyfinAuthentication->getUserId()}/Items"),
                ),
            [
                'Recursive' => 'true',
                'IncludeItemTypes' => 'Movie',
                'hasTmdbId' => 'true',
                'filters' => 'IsNotFolder',
                'fields' => 'ProviderIds',
                'limit' => 1000,
            ],
            jellyfinAccessToken: $jellyfinAuthentication->getAccessToken(),
        );

        $this->dbConnection->beginTransaction();

        $cachedJellyfinMovies = $this->fetchJellyfinMoviesByUserId($userId);

        foreach ($jellyfinPages as $jellyfinPage) {
            foreach ($jellyfinPage['Items'] as $jellyfinMovie) {
                $tmdbId = null;
                foreach ($jellyfinMovie['ProviderIds'] as $provider => $id) {
                    if ($provider === 'Tmdb') {
                        $tmdbId = (int)$id;

                        break;
                    }
                }

                $newWatched = $jellyfinMovie['UserData']['Played'];

                $cachedMovie = $cachedJellyfinMovies->getByItemId($jellyfinMovie['Id']);

                if ($cachedMovie !== null && $cachedMovie->getWatched() === $newWatched && $cachedMovie->getTmdbId() === $tmdbId) {
                    continue;
                }

                // TODO insert or update on duplicate
                $this->dbConnection->insert(
                    'user_jellyfin_cache',
                    [
                        'movary_user_id' => $userId,
                        'jellyfin_item_id' => $jellyfinMovie['Id'],
                        'tmdb_id' => $tmdbId,
                        'watched' => (int)$newWatched,
                    ],
                );
            }
        }

        $this->dbConnection->commit();
    }

    private function fetchJellyfinMoviesByUserId(int $userId) : JellyfinMovieDtoList
    {
        $result = $this->dbConnection->fetchAllAssociative(
            'SELECT * FROM user_jellyfin_cache JOIN user u on id = movary_user_id WHERE movary_user_id = ?',
            [$userId],
        );

        return JellyfinMovieDtoList::createFromArray($result);
    }
}
