<?php declare(strict_types=1);

namespace Movary\Api\Jellyfin;

use Movary\Api\Jellyfin\Dto\JellyfinAccessToken;
use Movary\Api\Jellyfin\Dto\JellyfinAuthenticationData;
use Movary\Api\Jellyfin\Dto\JellyfinUser;
use Movary\Api\Jellyfin\Dto\JellyfinUserId;
use Movary\Service\ServerSettings;
use Psr\Log\LoggerInterface;

class JellyfinApi
{
    public function __construct(
        private readonly JellyfinClient $jellyfinClient,
        private readonly ServerSettings $serverSettings,
        private readonly LoggerInterface $logger
    ) {
    }

    public function deleteJellyfinAccessToken() : void
    {
        $query = [
            'id' => $this->serverSettings->getJellyfinDeviceId()
        ];
        $this->jellyfinClient->delete('/Devices', $query);
        $this->logger->info('Jellyfin access token has been invalidated');
    }

    public function fetchJellyfinServerInfo() : ?array
    {
        return $this->jellyfinClient->get('/system/info/public');
    }

    public function fetchJellyfinUser(JellyfinUserId $jellyfinUserId) : ?JellyfinUser
    {
        $userInformation = $this->jellyfinClient->get('/Users/'.$jellyfinUserId);
        if($userInformation === null) {
            return null;
        }
        return JellyfinUser::create(JellyfinUserId::create($userInformation['Id']), $userInformation['Name']);
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

        $this->logger->info('Jellyfin account has been authenticated');

        return JellyfinAuthenticationData::create(
            JellyfinAccessToken::create((string)$response['AccessToken']),
            JellyfinUserId::create((string)$response['User']['Id']),
        );
    }
}
