<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Dto\JellyfinAuthenticationData;
use Movary\Api\Jellyfin\Dto\JellyfinUserid;
use Movary\Service\ServerSettings;

class JellyfinApi
{
    public function __construct(
        private readonly JellyfinClient $jellyfinClient,
        private readonly ServerSettings $serverSettings
    ) {}

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

    public function fetchJellyfinAuthenticationData(string $username, string $password) : ?JellyfinAuthenticationData
    {
        $data = [
            'Username' => $username,
            'Pw' => $password
        ];
        $response = $this->jellyfinClient->post('/Users/authenticatebyname', [], $data);
        return JellyfinAuthenticationData::create(
            JellyfinAccessToken::create((string)$response['AccessToken']),
            JellyfinUserid::create((string)$response['User']['Id'])
        );
    }
}