<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\Dto\PlexItem;
use Movary\Api\Plex\Exception\PlexAuthenticationError;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
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
        private readonly LoggerInterface $logger,
        private readonly PlexAuthenticationClient $authenticationClient,
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
    public function generatePlexAuthenticationUrl() : string
    {
        $response = '';
        $base_url = 'https://app.plex.tv/auth#?';
        $plexAuthenticationData = $this->authenticationClient->sendPostRequest('/pins');
        if($plexAuthenticationData !== []) {
            $this->userApi->updatePlexClientId($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['id']);
            $this->userApi->updateTemporaryPlexClientCode($this->authenticationService->getCurrentUserId(), $plexAuthenticationData['code']);
            $plexAppName = $plexAuthenticationData['product'];
            $plexClientIdentifier = $plexAuthenticationData['clientIdentifier'];
            $plexTemporaryClientCode = $plexAuthenticationData['code'];
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
            $urlCallback = $protocol . $_SERVER['HTTP_HOST'] . '/settings/plex/callback';
            $response =  $base_url . 'clientID=' . urlencode($plexClientIdentifier) . '&code=' . urlencode((string)$plexTemporaryClientCode) . '&' . urlencode('context[device][product]') . '=' . urlencode($plexAppName) . '&forwardUrl=' . urlencode($urlCallback);
        }
        return $response;
    }

    public function fetchPlexAccessToken(string $plexPinId, string $temporaryPlexClientCode) : ?PlexAccessToken
    {
        $query = [
            'code' => $temporaryPlexClientCode,
        ];
        try {
            $plexRequest = $this->authenticationClient->sendGetRequest('/pins/' . $plexPinId, $query);
            $plexAccessCode = PlexAccessToken::createPlexAccessToken($plexRequest['authToken']);
            return $plexAccessCode;
        } catch (PlexNotFoundError) {
            $this->logger->error('Plex pin does not exist');
            return null;
        }
    }

    public function verifyPlexAccessToken(PlexAccessToken $plexAccessToken) : bool
    {
        $query = [
            'X-Plex-Token' => $plexAccessToken->getPlexAccessTokenAsString()
        ];
        try {
            $this->authenticationClient->sendGetRequest('/user', $query);
            return true;
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return false;
        }
    }

    public function verifyPlexUrl(string $url) : bool
    {
        $query = [
            'X-Plex-Token' => $this->plexAccessToken->getPlexAccessTokenAsString()
        ];
        try {
            $request = $this->localClient->sendGetRequest('', $query, [], [], $url);
            if(is_array($request)) {
                return true;
            } else {
                return false;
            }
        } catch(PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return false;
        }
    }

    public function fetchPlexItem(int $itemId) : ?PlexItem
    {
        $query = [
            'X-Plex-Token' => $this->plexAccessToken->getPlexAccessTokenAsString()
        ];
        try {
            $item = $this->localClient->sendGetRequest('/library/metadata/' . $itemId, $query);
            $plexItem = PlexItem::createPlexItem($itemId, $item['type']);
            return $plexItem;
        } catch (PlexNotFoundError) {
            $this->logger->error('Plex item does not exist', ['itemId' => $itemId]);
            return null;
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return null;
        }
    }

    public function fetchPlexLibraries() : ? Array
    {
        $query = [
            'X-Plex-Token' => $this->plexAccessToken->getPlexAccessTokenAsString()
        ];
        try {
            $libraries = $this->localClient->sendGetRequest('/library/sections', $query);
            if(is_array($libraries)) {
                return $libraries;
            }
            return null;            
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return null;
        }
    }

    public function fetchWatchedPlexItems(int $libraryId) : ?Array
    {
        $query = [
            'X-Plex-Token' => $this->plexAccessToken->getPlexAccessTokenAsString(),
            'viewCount' => '!=0'
        ];
        try {
            $response = $this->localClient->sendGetRequest('/library/sections/' . $libraryId . '/all', $query);
            return $response;
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return false;
        }
    }

    public function fetchPlexWatchHistory() : ?Array
    {
        $query = [
            'X-Plex-Token' => $this->plexAccessToken->getPlexAccessTokenAsString(),
            'type' => '=movie'
        ];
        try {
            return $this->localClient->sendGetRequest('/status/sessions/history/all', $query);
            
        } catch (PlexAuthenticationError) {
            $this->logger->error('Plex access token is invalid');
            return false;
        }
    }
}