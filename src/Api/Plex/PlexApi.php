<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Api\Plex\Exception\PlexNotFoundError;
use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use RuntimeException;

/**
 * Very comprehensive unofficial documentation of Plex API: https://github.com/Arcanemagus/plex-api/wiki
 */
class PlexApi
{
    public function __construct(
        private readonly Authentication $authenticationService,
        private readonly UserApi $userApi,
        private readonly PlexClient $client,
    ) {
    }

    /**
     * For more info on authenticating with Plex: https://forums.plex.tv/t/authenticating-with-plex/609370
     */
    public function generatePlexAuth() : string
    {
        $response = '';
        $base_url = 'https://app.plex.tv/auth#?';
        $plexAuthenticationData = $this->client->sendPostRequest('/pins');
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
            $plexRequest = $this->client->sendGetRequest('/pins/' . $plexPinId, $query);
            $plexAccessCode = PlexAccessToken::createPlexAccessToken($plexRequest['authToken']);
            return $plexAccessCode;
        } catch (PlexNotFoundError) {
            return null;
        }
    }

    public function verifyPlexAccessToken(string $plexAccessToken) : bool
    {
        $query = [
            'X-Plex-Token' => $plexAccessToken
        ];
        try {
            $this->client->sendGetRequest('/user', $query);
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }
}