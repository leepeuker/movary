<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\Dto\PlexAccount;
use Movary\Api\Plex\Dto\PlexItem;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Api\Plex\Exception\PlexNoClientIdentifier;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\ServerSettings;
use Movary\ValueObject\PersonalRating;
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
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly ServerSettings $serverSettings,
        private readonly LoggerInterface $logger,
        private readonly PlexTvClient $plexTvClient,
        private readonly PlexLocalServerClient $localClient,
        private readonly PlexAccessToken $plexAccessToken,
        private readonly UserApi $userApi,
    ) {
    }

    /**
     * 1. A HTTP POST request will be sent to the Plex API, requesting a client ID and a client Code. The code is usually valid for 1800 seconds or 15 minutes. After 15min, a new code has to be requested.
     * 2. Both the pin ID and code will be stored in the database for later use in the plexCallback controller
     * 3. Based on the info returned by the Plex API, a new url will be generated, which looks like this: `https://app.plex.tv/auth#?clientID=<clientIdentifier>&code=<clientCode>&context[device][product]=<AppName>&forwardUrl=<urlCallback>`
     * 4. The URL is returned to the settingsController
     */
    public function generatePlexAuthenticationUrl() : ?string
    {
        try {
            $base_url = 'https://app.plex.tv/auth#?';
            $plexAuthenticationData = $this->plexTvClient->sendPostRequest('/pins');
            $this->userApi->updatePlexClientId($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['id']);
            $this->userApi->updateTemporaryPlexClientCode($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['code']);
            $plexAppName = $plexAuthenticationData['product'];
            $plexClientIdentifier = $plexAuthenticationData['clientIdentifier'];
            $plexTemporaryClientCode = $plexAuthenticationData['code'];
            $applicationUrl = $this->serverSettings->getApplicationUrl() ?? $_SERVER['SERVER_NAME'];
            $protocol = str_starts_with($applicationUrl, 'http://') ? '' : (str_starts_with($applicationUrl, 'https://') ? '' : (stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://'));
            $urlCallback = $protocol . $applicationUrl . '/settings/plex/callback';
            $response =  $base_url . 'clientID=' . urlencode($plexClientIdentifier) . '&code=' . urlencode((string)$plexTemporaryClientCode) . '&' . urlencode('context[device][product]') . '=' . urlencode($plexAppName) . '&forwardUrl=' . urlencode($urlCallback);
            return $response;
        } catch (PlexNoClientIdentifier) {
            return null;
        }
    }

    public function fetchPlexAccessToken(string $plexPinId, string $temporaryPlexClientCode) : ?PlexAccessToken
    {
        $query = [
            'code' => $temporaryPlexClientCode,
        ];
        try {
            $plexRequest = $this->plexTvClient->sendGetRequest('/pins/' . $plexPinId, [], $query);
            $plexAccessCode = PlexAccessToken::createPlexAccessToken($plexRequest['authToken']);
            return $plexAccessCode;
        } catch (PlexNotFoundError) {
            $this->logger->error('Plex pin does not exist');
            return null;
        }
    }

    public function fetchPlexAccount(PlexAccessToken $plexAccessToken) : ?PlexAccount
    {
        $query = [
            'X-Plex-Token' => $plexAccessToken->getPlexAccessTokenAsString()
        ];
        try {
            $accountData = $this->plexTvClient->sendGetRequest('/user', [], $query);
            $plexAccount = PlexAccount::createplexAccount((int)$accountData['id'], $accountData['username'], $accountData['email']);
            return $plexAccount;
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return null;
        }
    }

    public function verifyPlexUrl(string $url) : bool
    {
        $query = [
            'X-Plex-Token' => $this->plexAccessToken->getPlexAccessTokenAsString()
        ];
        try {
            $this->localClient->sendGetRequest('', $query, [], [], $url);
            return true;
        } catch(PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return false;
        }
    }
}