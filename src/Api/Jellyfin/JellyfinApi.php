<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use Movary\Api\Jellyfin\Cache\JellyfinMovieCache;
use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Dto\JellyfinAuthenticationData;
use Movary\Api\Jellyfin\Dto\JellyfinUser;
use Movary\Api\Jellyfin\Dto\JellyfinUserId;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\Exception\JellyfinServerUrlMissing;
use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;

class JellyfinApi
{
    public function __construct(
        private readonly JellyfinClient $jellyfinClient,
        private readonly ServerSettings $serverSettings,
        private readonly UserApi $userApi,
        private readonly JellyfinMovieCache $jellyfinMovieCache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createJellyfinAuthentication(int $userId, string $username, string $password) : JellyfinAuthenticationData
    {
        $jellyfinServerUrl = $this->userApi->findJellyfinServerUrl($userId);
        if ($jellyfinServerUrl === null) {
            throw JellyfinServerUrlMissing::create();
        }

        $url = $jellyfinServerUrl->appendRelativeUrl(RelativeUrl::create('/Users/authenticatebyname'));

        $data = [
            'Username' => $username,
            'Pw' => $password
        ];
        $response = $this->jellyfinClient->post($url, data: $data);
        if ($response === null) {
            throw new \RuntimeException('Missing authentication response body');
        }

        $this->logger->info('Jellyfin account has been authenticated for user: ' . $userId);

        return JellyfinAuthenticationData::create(
            JellyfinAccessToken::create((string)$response['AccessToken']),
            JellyfinUserId::create((string)$response['User']['Id']),
            $jellyfinServerUrl,
        );
    }

    public function deleteJellyfinAccessToken(JellyfinAuthenticationData $jellyfinAuthentication) : void
    {
        $url = $jellyfinAuthentication->getServerUrl()->appendRelativeUrl(RelativeUrl::create('/Users/'));

        $query = [
            'id' => $this->serverSettings->requireJellyfinDeviceId()
        ];
        $this->jellyfinClient->delete($url, $query, $jellyfinAuthentication->getAccessToken());
        $this->logger->info('Jellyfin access token has been invalidated');
    }

    public function fetchJellyfinServerInfo(Url $jellyfinServerUrl, JellyfinAccessToken $jellyfinAccessToken) : ?array
    {
        $url = $jellyfinServerUrl->appendRelativeUrl(RelativeUrl::create('/system/info'));

        return $this->jellyfinClient->get($url, jellyfinAccessToken: $jellyfinAccessToken);
    }

    public function fetchJellyfinServerInfoPublic(Url $jellyfinServerUrl) : ?array
    {
        $url = $jellyfinServerUrl->appendRelativeUrl(RelativeUrl::create('/system/info/public'));

        return $this->jellyfinClient->get($url);
    }

    public function findJellyfinUser(JellyfinAuthenticationData $jellyfinAuthentication) : ?JellyfinUser
    {
        $relativeUrl = RelativeUrl::create('/Users/' . $jellyfinAuthentication->getUserId());

        $url = $jellyfinAuthentication->getServerUrl()->appendRelativeUrl($relativeUrl);

        try {
            $userInformation = $this->jellyfinClient->get($url, jellyfinAccessToken: $jellyfinAuthentication->getAccessToken(), timeout: 2);
        } catch (\Exception) {
            return null;
        }

        if ($userInformation === null) {
            return null;
        }

        return JellyfinUser::create(JellyfinUserId::create($userInformation['Id']), $userInformation['Name']);
    }

    public function setMoviesWatchState(int $userId, array $watchedTmdbIds, array $unwatchedTmdbIds) : void
    {
        $jellyfinAuthentication = $this->userApi->findJellyfinAuthentication($userId);
        if ($jellyfinAuthentication === null) {
            throw JellyfinInvalidAuthentication::create();
        }

        $combinedTmdbIds = array_merge($watchedTmdbIds, $unwatchedTmdbIds);

        foreach ($this->jellyfinMovieCache->fetchJellyfinMoviesByTmdbIds($userId, $combinedTmdbIds) as $jellyfinMovie) {
            $this->setMovieWatchState(
                $userId,
                $jellyfinAuthentication,
                $jellyfinMovie,
                in_array($jellyfinMovie->getTmdbId(), $watchedTmdbIds),
            );
        }
    }

    private function setMovieWatchState(
        int $userId,
        JellyfinAuthenticationData $jellyfinAuthentication,
        Dto\JellyfinMovieDto $jellyfinMovie,
        bool $watched,
    ) : void {
        $relativeUrl = RelativeUrl::create(
            sprintf(
                '/Users/%s/PlayedItems/%s',
                $jellyfinAuthentication->getUserId(),
                $jellyfinMovie->getJellyfinItemId(),
            ),
        );

        $url = $jellyfinAuthentication->getServerUrl()->appendRelativeUrl($relativeUrl);

        if ($watched === true) {
            $this->jellyfinClient->post($url, jellyfinAccessToken: $jellyfinAuthentication->getAccessToken());
        } else {
            $this->jellyfinClient->delete($url, jellyfinAccessToken: $jellyfinAuthentication->getAccessToken());
        }

        $this->logger->info(
            'Jellyfin sync: Movie watch state updated',
            [
                'userId' => $userId,
                'tmdbId' => $jellyfinMovie->getJellyfinItemId(),
                'itemId' => $jellyfinMovie->getJellyfinItemId(),
                'watchedState' => $watched
            ],
        );
    }
}
