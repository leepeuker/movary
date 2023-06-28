<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\Dto\PlexAccount;
use Movary\Api\Plex\Dto\PlexItem;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Api\Plex\Exception\PlexNoClientIdentifier;
use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
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
    private const BASE_URL = 'https://app.plex.tv/auth#?';

    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ServerSettings $serverSettings,
        private readonly LoggerInterface $logger,
        private readonly PlexTvClient $plexTvClient,
        private readonly PlexLocalServerClient $localClient,
        private readonly UserApi $userApi,
    ) {
    }

    public function findPlexAccessToken(string $plexPinId, string $temporaryPlexClientCode) : ?PlexAccessToken
    {
        $headers = [
            'code' => $temporaryPlexClientCode,
        ];

        $relativeUrl = RelativeUrl::createFromString('/pins/' . $plexPinId);

        try {
            $plexRequest = $this->plexTvClient->get($relativeUrl, $headers);
        } catch (PlexNotFoundError) {
            $this->logger->error('Plex pin does not exist');

            return null;
        }

        return PlexAccessToken::createPlexAccessToken($plexRequest['authToken']);
    }

    public function findPlexAccount(PlexAccessToken $plexAccessToken) : ?PlexAccount
    {
        $headers = [
            'X-Plex-Token' => $plexAccessToken->getPlexAccessTokenAsString()
        ];

        $relativeUrl = RelativeUrl::createFromString('/user');

        try {
            $accountData = $this->plexTvClient->get($relativeUrl, $headers);
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');

            return null;
        }

        return PlexAccount::createPlexAccount((int)$accountData['id'], $accountData['username']);
    }

    /**
     * 1. A HTTP POST request will be sent to the Plex API, requesting a client ID and a client Code. The code is usually valid for 1800 seconds or 15 minutes. After 15min, a new code has to be requested.
     * 2. Both the pin ID and code will be stored in the database for later use in the plexCallback controller
     * 3. Based on the info returned by the Plex API, a new url will be generated, which looks like this: `https://app.plex.tv/auth#?clientID=<clientIdentifier>&code=<clientCode>&context[device][product]=<AppName>&forwardUrl=<urlCallback>`
     * 4. The URL is returned to the settingsController
     */
    public function generatePlexAuthenticationUrl() : ?string
    {
        $relativeUrl = RelativeUrl::createFromString('/pins');

        try {
            $plexAuthenticationData = $this->plexTvClient->sendPostRequest($relativeUrl);
        } catch (PlexNoClientIdentifier) {
            return null;
        }

        $this->userApi->updatePlexClientId($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['id']);
        $this->userApi->updateTemporaryPlexClientCode($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['code']);

        $plexAppName = $plexAuthenticationData['product'];
        $plexClientIdentifier = $plexAuthenticationData['clientIdentifier'];
        $plexTemporaryClientCode = $plexAuthenticationData['code'];

        $applicationUrl = $this->serverSettings->getApplicationUrl();
        if ($applicationUrl === null) {
            return null;
        }

        $urlCallback = $applicationUrl . '/settings/plex/callback';

        $getParameters = [
            'clientID' => $plexClientIdentifier,
            'code' => (string)$plexTemporaryClientCode,
            'context[device][product]' => $plexAppName,
            'forwardUrl' => $urlCallback,
        ];

        return self::BASE_URL . http_build_query($getParameters);
    }

    public function verifyPlexUrl(int $userId, Url $url) : bool
    {
        $query = [
            'X-Plex-Token' => $this->userApi->fetchUser($userId)->getPlexAccessToken()
        ];

        try {
            $this->localClient->sendGetRequest($url, $query);

            return true;
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');

            return false;
        }
    }
}
