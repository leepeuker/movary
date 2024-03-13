<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use Exception;
use Movary\Api\Jellyfin\Cache\JellyfinCache;
use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Dto\JellyfinAuthenticationData;
use Movary\Api\Jellyfin\Dto\JellyfinUser;
use Movary\Api\Jellyfin\Dto\JellyfinUserId;
use Movary\Api\Jellyfin\Exception\JellyfinInvalidAuthentication;
use Movary\Api\Jellyfin\Exception\JellyfinServerUrlMissing;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use Movary\ValueObject\Date;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
use Psr\Log\LoggerInterface;
use RuntimeException;

class JellyfinApi
{
    public function __construct(
        private readonly JellyfinClient $jellyfinClient,
        private readonly ServerSettings $serverSettings,
        private readonly UserApi $userApi,
        private readonly JellyfinCache $jellyfinMovieCache,
        private readonly MovieHistoryApi $movieHistoryApi,
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
            throw new RuntimeException('Missing authentication response body');
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
        } catch (Exception) {
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
        $tmdbIdsToLastWatchDateMap = $this->movieHistoryApi->fetchTmdbIdsToLastWatchDatesMap($userId, $watchedTmdbIds);

        foreach ($this->jellyfinMovieCache->fetchJellyfinMoviesByTmdbIds($userId, $combinedTmdbIds) as $jellyfinMovie) {
            $tmdbId = $jellyfinMovie->getTmdbId();
            $watched = in_array($tmdbId, $watchedTmdbIds);
            $lastWatchDate = $tmdbIdsToLastWatchDateMap[$tmdbId] ?? null;

            $this->setMovieWatchState(
                $userId,
                $jellyfinAuthentication,
                $jellyfinMovie,
                $watched,
                $lastWatchDate,
            );
        }
    }

    private function setMovieWatchState(
        int $userId,
        JellyfinAuthenticationData $jellyfinAuthentication,
        Dto\JellyfinMovieDto $jellyfinMovie,
        bool $watched,
        ?Date $lastWatchDate,
    ) : void {
        $relativeUrl = RelativeUrl::create(
            sprintf(
                '/Users/%s/PlayedItems/%s',
                $jellyfinAuthentication->getUserId(),
                $jellyfinMovie->getJellyfinItemId(),
            ),
        );

        $url = $jellyfinAuthentication->getServerUrl()->appendRelativeUrl($relativeUrl);

        $currentLastWatchDateJellyfin = $jellyfinMovie->getLastWatchDate();
        if ($watched === true &&
            $currentLastWatchDateJellyfin !== null &&
            $lastWatchDate !== null &&
            $currentLastWatchDateJellyfin->isEqual($lastWatchDate) === true) {
            $this->logger->debug(
                'Jellyfin export: Skipped movie play, no change',
                [
                    'userId' => $userId,
                    'tmdbId' => $jellyfinMovie->getJellyfinItemId(),
                    'itemId' => $jellyfinMovie->getJellyfinItemId(),
                    'watchedState' => $watched,
                    'lastWatchDate' => (string)$lastWatchDate,
                ],
            );

            return;
        }

        if ($watched === false) {
            $this->jellyfinClient->delete($url, jellyfinAccessToken: $jellyfinAuthentication->getAccessToken());

            $this->logger->info(
                'Jellyfin export: Movie play deleted',
                [
                    'userId' => $userId,
                    'tmdbId' => $jellyfinMovie->getJellyfinItemId(),
                    'itemId' => $jellyfinMovie->getJellyfinItemId(),
                ],
            );

            return;
        }

        $this->jellyfinClient->post(
            $url,
            ['datePlayed' => (string)$lastWatchDate],
            jellyfinAccessToken: $jellyfinAuthentication->getAccessToken(),
        );

        $this->logger->info(
            'Jellyfin export: Movie play added',
            [
                'userId' => $userId,
                'tmdbId' => $jellyfinMovie->getTmdbId(),
                'itemId' => $jellyfinMovie->getJellyfinItemId(),
                'oldLastWatchDate' => (string)$currentLastWatchDateJellyfin,
                'newLastWatchDate' => (string)$lastWatchDate,
            ],
        );
    }
}
