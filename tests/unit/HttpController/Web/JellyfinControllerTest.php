<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\Domain\User\UserApi;
use Movary\HttpController\Web\JellyfinController;
use Movary\Service\Jellyfin\JellyfinScrobbler;
use Movary\Service\WebhookUrlBuilder;
use Movary\Util\UrlValidator;
use Movary\Util\ValidatedUrl;
use Movary\ValueObject\Exception\InvalidSafeUrl;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Url;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(\Movary\HttpController\Web\JellyfinController::class)]
#[AllowMockObjectsWithoutExpectations]
class JellyfinControllerTest extends TestCase
{
    public function testSaveServerUrlAlwaysValidatesDestination() : void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('requireWebSession')->willReturn(
            AuthenticatedUser::create(12, CredentialType::WEB_SESSION),
        );
        $userApi = $this->createMock(UserApi::class);
        $urlValidator = $this->createMock(UrlValidator::class);
        $serverUrl = Url::createFromString('https://jellyfin.example');
        $urlValidator
            ->expects(self::once())
            ->method('validateUrlIsSafe')
            ->with($serverUrl, [])
            ->willReturn($this->createStub(ValidatedUrl::class));
        $userApi->expects(self::once())->method('updateJellyfinServerUrl')->with(12, $serverUrl);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())->method('getBody')->willReturn(
            '{"JellyfinServerUrl":"https://jellyfin.example"}',
        );
        $subject = new JellyfinController(
            $authentication,
            $userApi,
            $this->createMock(JellyfinScrobbler::class),
            $this->createMock(WebhookUrlBuilder::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(JellyfinApi::class),
            $urlValidator,
        );

        $response = $subject->saveJellyfinServerUrl($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
    }

    public function testSaveServerUrlReturnsSafeValidationReason() : void
    {
        $authentication = $this->createStub(Authentication::class);
        $authentication->method('requireWebSession')->willReturn(
            AuthenticatedUser::create(12, CredentialType::WEB_SESSION),
        );
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::never())->method('updateJellyfinServerUrl');
        $urlValidator = $this->createStub(UrlValidator::class);
        $urlValidator->method('validateUrlIsSafe')->willThrowException(
            InvalidSafeUrl::createForDisallowedDestination('jellyfin.local', '192.168.1.10'),
        );
        $request = $this->createStub(Request::class);
        $request->method('getBody')->willReturn('{"JellyfinServerUrl":"http://jellyfin.local:8096"}');
        $subject = new JellyfinController(
            $authentication,
            $userApi,
            $this->createStub(JellyfinScrobbler::class),
            $this->createStub(WebhookUrlBuilder::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(JellyfinApi::class),
            $urlValidator,
        );

        $response = $subject->saveJellyfinServerUrl($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        self::assertSame(
            'Server URL resolves to a private or reserved IP address.',
            $response->getBody(),
        );
    }
}
