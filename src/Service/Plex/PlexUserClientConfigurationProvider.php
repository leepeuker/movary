<?php declare(strict_types=1);

namespace Movary\Service\Plex;

use Movary\Api\Plex\Dto\PlexUserClientConfiguration;
use Movary\Api\Plex\Exception\PlexAuthenticationTokenMissing;
use Movary\Api\Plex\Exception\PlexServerUrlMissing;
use Movary\Domain\User\UserApi;

class PlexUserClientConfigurationProvider
{
    public function __construct(private readonly UserApi $userApi)
    {
    }

    public function provideUserClientConfiguration(int $userId) : PlexUserClientConfiguration
    {
        $user = $this->userApi->fetchUser($userId);

        $plexToken = $user->getPlexAccessToken();
        if ($plexToken === null) {
            throw PlexAuthenticationTokenMissing::create();
        }

        $plexServerUrl = $user->getPlexServerUrl();
        if ($plexServerUrl === null) {
            throw PlexServerUrlMissing::create();
        }

        return PlexUserClientConfiguration::create($plexToken, $plexServerUrl);
    }
}
