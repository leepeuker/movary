<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\EmailVerificationRequestService;
use Movary\Domain\User\Service\EmailVerificationTokenService;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\HttpController\Web\EmailVerificationController;
use Movary\Service\ApplicationUrlService;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment;

#[CoversClass(EmailVerificationController::class)]
#[AllowMockObjectsWithoutExpectations]
class EmailVerificationControllerTest extends TestCase
{
    private ApplicationUrlService&MockObject $applicationUrlServiceMock;

    private Authentication&MockObject $authenticationMock;

    private EmailVerificationRequestService&MockObject $requestServiceMock;

    private EmailVerificationController $subject;

    private EmailVerificationTokenService&MockObject $tokenServiceMock;

    private Environment&MockObject $twigMock;

    protected function setUp() : void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->requestServiceMock = $this->createMock(EmailVerificationRequestService::class);
        $this->tokenServiceMock = $this->createMock(EmailVerificationTokenService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->subject = new EmailVerificationController(
            $this->twigMock,
            $this->requestServiceMock,
            $this->tokenServiceMock,
            $this->applicationUrlServiceMock,
            $this->createStub(LoggerInterface::class),
            $this->authenticationMock,
        );
    }

    public function testRenderVerificationPageShowsConfirmationForValidToken() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn(['token' => 'verification-token']);
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/email-verification.html.twig', [
                'token' => 'verification-token',
                'tokenValid' => true,
                'genericError' => false,
            ])
            ->willReturn('confirmation');

        $response = $this->subject->renderVerificationPage($request);

        self::assertSame('confirmation', $response->getBody());
        $this->assertSensitiveHeaders($response);
    }

    public function testRenderVerificationPageShowsInvalidLink() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getGetParameters')->willReturn(['token' => 'invalid-token']);
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(null);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/email-verification.html.twig', self::callback(
                static fn(array $data) : bool => $data['tokenValid'] === false,
            ));

        $response = $this->subject->renderVerificationPage($request);

        $this->assertSensitiveHeaders($response);
    }

    public function testVerifyEmailConsumesTokenAndRedirects() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn(['token' => 'verification-token']);
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('verifyEmail')
            ->with('verification-token')
            ->willReturn(true);
        $this->authenticationMock
            ->method('authenticateWebSession')
            ->willReturn(AuthenticatedUser::create(12, CredentialType::WEB_SESSION));
        $this->authenticationMock->expects(self::once())->method('logout');
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(
                static fn(RelativeUrl $url) : bool => (string)$url === '/login?email-verification=success',
            ))
            ->willReturn('/movary/login?email-verification=success');

        $response = $this->subject->verifyEmail($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertContains(
            'Location: /movary/login?email-verification=success',
            array_map('strval', $response->getHeaders()),
        );
        $this->assertSensitiveHeaders($response);
    }

    public function testVerifyEmailDoesNotConsumeInvalidToken() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn(['token' => 'invalid-token']);
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(null);
        $this->tokenServiceMock->expects(self::never())->method('verifyEmail');
        $this->twigMock->method('render')->willReturn('invalid');

        $response = $this->subject->verifyEmail($request);

        self::assertSame('invalid', $response->getBody());
        $this->assertSensitiveHeaders($response);
    }

    public function testResendAlwaysRendersNeutralConfirmation() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn(['email' => 'alice@example.com']);
        $this->requestServiceMock
            ->expects(self::once())
            ->method('request')
            ->with('alice@example.com')
            ->willReturn(false);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/email-verification-requested.html.twig')
            ->willReturn('neutral confirmation');

        $response = $this->subject->resend($request);

        self::assertSame('neutral confirmation', $response->getBody());
        $this->assertSensitiveHeaders($response);
    }

    private function assertSensitiveHeaders(Response $response) : void
    {
        $headers = array_map('strval', $response->getHeaders());

        self::assertContains('Cache-Control: no-store', $headers);
        self::assertContains('Pragma: no-cache', $headers);
        self::assertContains('Referrer-Policy: no-referrer', $headers);
    }
}
