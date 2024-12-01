<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\Dto\PlexAccount;
use Movary\Api\Plex\Dto\PlexUserClientConfiguration;
use Movary\Api\Plex\Exception\PlexAuthenticationInvalid;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
use Movary\ValueObject\Year;
use Psr\Log\LoggerInterface;

/**
 * @link https://github.com/Arcanemagus/plex-api/wiki Comprehensive documentation of the Plex API
 * @link https://forums.plex.tv/t/authenticating-with-plex/609370 For more info on authenticating with Plex
 *
 * The authentication flow is as follows:
 * 1. The user visits /settings/plex
 * 2. The settingsController will check if an access token exists in the database.
 * 3. If the acess token does not exist or the access token is invalid, a new authentication URL will be generated with generatePlexAuthenticationUrl(). See more on info there.
 * 4. The URL will be returned to the settingsController and injected in the Plex settings page.
 * 5. When the user clicks on the 'login' button, they'll be redirected to the url, authenticate and return to url stated in the forwardUrl parameter.
 * 6. The user returns to the URL callback and the callback controller will fetch the Plex Access Token.
 * 7. After fetching this, and storing it in the database, the user returns to the Plex Settings page.
 */
class PlexApi
{
    private const string BASE_URL = 'https://app.plex.tv/auth#?';

    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ServerSettings $serverSettings,
        private readonly LoggerInterface $logger,
        private readonly PlexTvClient $plexTvClient,
        private readonly PlexUserClient $userClient,
        private readonly UserApi $userApi,
        private readonly MovieApi $movieApi,
    ) {
    }

    public function fetchWatchlist(PlexAccessToken $plexAccessToken) : array
    {
        $query = [
            'type' => '1',
            'includeFields' => 'title,type,year,key',
            'includeElements' => 'Guid',
        ];

        $relativeUrl = RelativeUrl::create('/library/sections/watchlist/all');

        $limit = 30;
        $offset = 0;

        $watchlistMovies = [];

        do {
            $responseData = $this->plexTvClient->getMetadata($plexAccessToken, $relativeUrl, $query, $limit, $offset);

            $offset += $limit;

            $totalItems = $responseData['MediaContainer']['totalSize'];

            foreach ($responseData['MediaContainer']['Metadata'] as $movie) {
                $watchlistMovies[] = $movie;
            }
        } while ($totalItems > $offset);

        return $watchlistMovies;
    }

    public function findPlexAccessToken(string $plexPinId, string $temporaryPlexClientCode) : ?PlexAccessToken
    {
        $headers = [
            'code' => $temporaryPlexClientCode,
        ];

        $relativeUrl = RelativeUrl::create('/pins/' . $plexPinId);

        try {
            $plexRequest = $this->plexTvClient->get($relativeUrl, $headers);
        } catch (PlexNotFoundError) {
            $this->logger->error('Plex pin does not exist');

            return null;
        }

        return PlexAccessToken::create($plexRequest['authToken']);
    }

    public function findPlexAccount(PlexAccessToken $plexAccessToken) : ?PlexAccount
    {
        $headers = [
            'X-Plex-Token' => (string)$plexAccessToken
        ];

        $relativeUrl = RelativeUrl::create('/user');

        try {
            $accountData = $this->plexTvClient->get($relativeUrl, $headers);
        } catch (PlexAuthenticationInvalid) {
            $this->logger->error('Plex access token is invalid');

            return null;
        }

        return PlexAccount::create((int)$accountData['id'], $accountData['username']);
    }

    public function findTmdbIdsOfWatchlistMovies(PlexAccessToken $plexAccessToken, array $plexWatchlistMovies) : array
    {
        $tmdbIds = [];

        foreach ($plexWatchlistMovies as $plexWatchlistMovie) {
            $moviePlexTitle = $plexWatchlistMovie['title'];
            $moviePlexYear = Year::createFromInt($plexWatchlistMovie['year']);

            $movie = $this->movieApi->findByTitleAndYear($moviePlexTitle, $moviePlexYear);

            $tmdbId = $movie?->getTmdbId();

            if ($tmdbId !== null) {
                $tmdbIds[] = $tmdbId;

                $this->logger->debug(
                    'Plex Api - Found tmdb id locally',
                    [
                        'tmdbId' => (string)$tmdbId,
                        'plexTitle' => $moviePlexTitle,
                        'plexYear' => (string)$moviePlexYear,
                    ],
                );

                continue;
            }

            $movieData = $this->plexTvClient->getMetadata($plexAccessToken, RelativeUrl::create($plexWatchlistMovie['key']));

            $tmdbId = null;

            foreach ($movieData['MediaContainer']['Metadata'][0]['Guid'] as $guid) {
                if (str_starts_with($guid['id'], 'tmdb') === false) {
                    continue;
                }

                $tmdbId = str_replace('tmdb://', '', $guid['id']);

                $tmdbIds[] = $tmdbId;

                $this->logger->debug(
                    'Plex Api - Found tmdb id on plex',
                    [
                        'tmdbId' => $tmdbId,
                        'plexTitle' => $moviePlexTitle,
                        'plexYear' => (string)$moviePlexYear,
                    ],
                );

                break;
            }

            if ($tmdbId === null) {
                $this->logger->debug(
                    'Plex Api - Could not find tmdb id',
                    [
                        'plexTitle' => $moviePlexTitle,
                        'plexYear' => (string)$moviePlexYear,
                    ],
                );
            }
        }

        return $tmdbIds;
    }

    /**
     * 1. A HTTP POST request will be sent to the Plex API, requesting a client ID and a client Code. The code is usually valid for 1800 seconds or 15 minutes. After 15min, a new code has to be requested.
     * 2. Both the pin ID and code will be stored in the database for later use in the plexCallback controller
     * 3. Based on the info returned by the Plex API, a new url will be generated, which looks like this: `https://app.plex.tv/auth#?clientID=<clientIdentifier>&code=<clientCode>&context[device][product]=<AppName>&forwardUrl=<urlCallback>`
     * 4. The URL is returned to the settingsController
     */
    public function generatePlexAuthenticationUrl() : string
    {
        $relativeUrl = RelativeUrl::create('/pins');

        $plexAuthenticationData = $this->plexTvClient->post($relativeUrl);

        $this->userApi->updatePlexClientId($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['id']);
        $this->userApi->updateTemporaryPlexClientCode($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['code']);

        $plexAppName = $plexAuthenticationData['product'];
        $plexClientIdentifier = $plexAuthenticationData['clientIdentifier'];
        $plexTemporaryClientCode = $plexAuthenticationData['code'];

        $applicationUrl = $this->serverSettings->requireApplicationUrl();

        $getParameters = [
            'clientID' => $plexClientIdentifier,
            'code' => (string)$plexTemporaryClientCode,
            'context[device][product]' => $plexAppName,
            'forwardUrl' => (string)Url::createFromString(trim($applicationUrl, '/') . '/settings/plex/callback'),
        ];

        return self::BASE_URL . http_build_query($getParameters);
    }

    public function testUserClientConfiguration(PlexUserClientConfiguration $userClientConfiguration) : bool
    {
        try {
            $this->userClient->get($userClientConfiguration);

            return true;
        } catch (PlexAuthenticationInvalid) {
            $this->logger->error('Plex access token is invalid');

            return false;
        }
    }
}
