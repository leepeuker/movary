<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Dto\JellyfinAuthenticationData;
use Movary\Api\Jellyfin\Dto\JellyfinUserId;
use Movary\Service\ServerSettings;

class JellyfinApi
{
    public function __construct(
        private readonly JellyfinClient $jellyfinClient,
        private readonly ServerSettings $serverSettings,
    ) {
    }

    public function deleteJellyfinAccessToken() : void
    {
        $query = [
            'id' => $this->serverSettings->getJellyfinDeviceId()
        ];
        $this->jellyfinClient->delete('/Devices', $query);
    }

    public function fetchJellyfinServerInfo() : ?array
    {
        return $this->jellyfinClient->get('/system/info/public');
    }

    public function createJellyfinAuthentication(string $username, string $password) : ?JellyfinAuthenticationData
    {
        $data = [
            'Username' => $username,
            'Pw' => $password
        ];
        $response = $this->jellyfinClient->post('/Users/authenticatebyname', data: $data);
        if ($response === null) {
            return null;
        }

        return JellyfinAuthenticationData::create(
            JellyfinAccessToken::create((string)$response['AccessToken']),
            JellyfinUserId::create((string)$response['User']['Id']),
        );
    }
}
