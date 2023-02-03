<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Domain\User\Service\Authentication;

/**
 * Very comprehensive unofficial documentation of Plex API: https://github.com/Arcanemagus/plex-api/wiki
 */
class PlexApi
{
    private const APP_NAME = 'Plex Movary';
    public function __construct(
        private readonly PlexClient $client,
        private readonly Authentication $authentication
    ) {
    }

    /**
     * For more info on authenticating with Plex: https://forums.plex.tv/t/authenticating-with-plex/609370
     */
    public function generateAuthUrl() : string
    {
        $response = '';
        $base_url = 'https://app.plex.tv/auth#?';
        $headers = [
            'Accept' => 'application/json',
            'X-Plex-Product' => self::APP_NAME
        ];
        $plexJsonPayload = $this->client->sendPostRequest('/pins', $headers);
        if($plexJsonPayload !== []) {
            $plexClientIdentifier = $plexJsonPayload['clientIdentifier'];
            $plexAuthCode = $plexJsonPayload['code'];
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === 0 ? 'https://' : 'http://';
            $urlCallback = $protocol . $_SERVER['HTTP_HOST'] . '/settings/plex/callback';
            $response =  $base_url . 'clientID=' . urlencode($plexClientIdentifier) . '&code=' . urlencode((string)$plexAuthCode) . '&' . urlencode('context[device][product]') . '=' . urlencode(self::APP_NAME) . '&forwardUrl=' . urlencode($urlCallback);
        }
        return $response;
    }
}