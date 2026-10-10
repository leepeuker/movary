<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Api\Plex\Dto\PlexAccessToken;
use Movary\Api\Plex\PlexApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\PlexController;
use Movary\Service\ApplicationUrlService;
use Movary\Service\Plex\PlexScrobbler;
use Movary\Service\PlexCallbackStateService;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\UrlValidator;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(\Movary\HttpController\Web\PlexController::class)]
#[AllowMockObjectsWithoutExpectations]
class PlexControllerTest extends TestCase
{
    private MockObject|Authentication $authentication;

    private MockObject|PlexApi $plexApi;

    private MockObject|PlexCallbackStateService $stateService;

    private PlexController $subject;

    private MockObject|UserApi $userApi;

    protected function setUp() : void
    {
        $this->authentication = $this->createMock(Authentication::class);
        $this->userApi = $this->createMock(UserApi::class);
        $this->plexApi = $this->createMock(PlexApi::class);
        $this->stateService = $this->createMock(PlexCallbackStateService::class);
        $this->subject = new PlexController(
            $this->authentication,
            $this->userApi,
            $this->createMock(PlexScrobbler::class),
            $this->plexApi,
            $this->createMock(WebhookUrlBuilder::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ApplicationUrlService::class),
            $this->createMock(UrlValidator::class),
            $this->stateService,
        );
    }

    public function testAuthenticationUrlBindsStateToAuthenticationCookie() : void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects(self::once())
            ->method('getCookie')
            ->with(Authentication::AUTHENTICATION_COOKIE_NAME)
            ->willReturn('authentication-token');
        $this->authentication->expects(self::once())->method('requireWebSession')->willReturn(AuthenticatedUser::create(12, CredentialType::WEB_SESSION));
        $this->userApi->expects(self::once())->method('findPlexAccessToken')->with(12)->willReturn(null);
        $this->plexApi
            ->expects(self::once())
            ->method('generatePlexAuthenticationUrl')
            ->with(12, 'authentication-token')
            ->willReturn('https://app.plex.tv/auth');

        $response = $this->subject->generatePlexAuthenticationUrl($request);

        self::assertSame('{"authenticationUrl":"https:\/\/app.plex.tv\/auth"}', $response->getBody());
    }

    public function testCallbackRejectsInvalidStateBeforePlexLookup() : void
    {
        $request = $this->createCallbackRequest();
        $this->prepareStoredCallbackData();
        $this->stateService
            ->expects(self::once())
            ->method('isValid')
            ->with('callback-state', 'pin-id', 'plex-code', 'authentication-token')
            ->willReturn(false);
        $this->plexApi->expects(self::never())->method('findPlexAccessToken');
        $this->userApi->expects(self::never())->method('updatePlexAccessToken');

        $response = $this->subject->processPlexCallback($request);

        self::assertSame(403, $response->getStatusCode()->getCode());
    }

    public function testCallbackClearsTemporaryPlexDataAfterSuccess() : void
    {
        $request = $this->createCallbackRequest();
        $this->prepareStoredCallbackData();
        $this->stateService->expects(self::once())->method('isValid')->willReturn(true);
        $plexAccessToken = PlexAccessToken::create('plex-access-token');
        $this->plexApi
            ->expects(self::once())
            ->method('findPlexAccessToken')
            ->with('pin-id', 'plex-code')
            ->willReturn($plexAccessToken);
        $this->plexApi->expects(self::once())->method('findPlexAccount')->with($plexAccessToken)->willReturn(null);
        $this->userApi->expects(self::once())->method('updatePlexAccessToken')->with(12, 'plex-access-token');
        $this->userApi->expects(self::once())->method('updatePlexClientId')->with(12, null);
        $this->userApi->expects(self::once())->method('updateTemporaryPlexClientCode')->with(12, null);

        $response = $this->subject->processPlexCallback($request);

        self::assertSame(303, $response->getStatusCode()->getCode());
    }

    private function createCallbackRequest() : Request&MockObject
    {
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getGetParameters')->willReturn(['state' => 'callback-state']);
        $request
            ->expects(self::once())
            ->method('getCookie')
            ->with(Authentication::AUTHENTICATION_COOKIE_NAME)
            ->willReturn('authentication-token');

        return $request;
    }

    private function prepareStoredCallbackData() : void
    {
        $this->authentication->expects(self::once())->method('requireWebSession')->willReturn(AuthenticatedUser::create(12, CredentialType::WEB_SESSION));
        $this->userApi->expects(self::once())->method('findPlexClientId')->with(12)->willReturn('pin-id');
        $this->userApi->expects(self::once())->method('findTemporaryPlexCode')->with(12)->willReturn('plex-code');
    }
}
