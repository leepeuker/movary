<?php declare(strict_types=1);

namespace Movary\Api\Plex;

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
        $base_url = 'https://app.plex.tv/auth#!?';
        $plexJsonPayload = $this->client->sendPostRequest('/pins');
        if($plexJsonPayload !== []) {
            $this->userApi->updatePlexAccessToken($this->authenticationService->getCurrentUserId(), $plexJsonPayload['id']);
            $plexAppName = $plexJsonPayload['product'];
            $plexClientIdentifier = $plexJsonPayload['clientIdentifier'];
            $plexAuthCode = $plexJsonPayload['code'];
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
            $urlCallback = $protocol . $_SERVER['HTTP_HOST'] . '/settings/plex';
            $response =  $base_url . 'clientID=' . urlencode($plexClientIdentifier) . '&code=' . urlencode((string)$plexAuthCode) . '&' . urlencode('context[device][product]') . '=' . urlencode($plexAppName) . '&forwardUrl=' . urlencode($urlCallback);
        }
        return $response;
    }

    public function verifyPlexAccessToken(string $plexAccessToken) : bool
    {
        $headers = [
            'X-Plex-Token' => $plexAccessToken
        ];
        try {
            $this->client->sendGetRequest('/user', $headers);
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }
}