<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Plex;

use Movary\Api\Plex\PlexApi;
use Movary\Api\Plex\PlexTvClient;
use Movary\Api\Plex\PlexUserClient;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Service\PlexCallbackStateService;
use Movary\Service\ServerSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(\Movary\Api\Plex\PlexApi::class)]
class PlexApiTest extends TestCase
{
    public function testAuthenticationUrlContainsSignedCallbackState() : void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::exactly(2))->method('getCurrentUserId')->willReturn(12);
        $serverSettings = $this->createMock(ServerSettings::class);
        $serverSettings
            ->expects(self::once())
            ->method('requireApplicationUrl')
            ->willReturn('https://movary.example/');
        $plexTvClient = $this->createMock(PlexTvClient::class);
        $plexTvClient
            ->expects(self::once())
            ->method('post')
            ->willReturn([
                'id' => 123,
                'code' => 'plex-code',
                'product' => 'Movary',
                'clientIdentifier' => 'plex-client',
            ]);
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::once())->method('updatePlexClientId')->with(12, 123);
        $userApi->expects(self::once())->method('updateTemporaryPlexClientCode')->with(12, 'plex-code');
        $stateService = $this->createMock(PlexCallbackStateService::class);
        $stateService
            ->expects(self::once())
            ->method('create')
            ->with('123', 'plex-code', 'authentication-token')
            ->willReturn('signed-state');
        $subject = new PlexApi(
            $authentication,
            $serverSettings,
            $this->createStub(LoggerInterface::class),
            $plexTvClient,
            $this->createStub(PlexUserClient::class),
            $userApi,
            $this->createStub(MovieApi::class),
            $stateService,
        );

        $authenticationUrl = $subject->generatePlexAuthenticationUrl('authentication-token');
        $fragment = parse_url($authenticationUrl, PHP_URL_FRAGMENT);
        self::assertIsString($fragment);
        parse_str(ltrim($fragment, '?'), $parameters);

        self::assertSame('plex-client', $parameters['clientID']);
        self::assertSame('plex-code', $parameters['code']);
        self::assertSame(
            'https://movary.example/settings/plex/callback?state=signed-state',
            $parameters['forwardUrl'],
        );
    }
}
