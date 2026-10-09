<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\LoginAttemptLimitReached;
use Movary\Domain\User\Service\Authentication;
use Movary\HttpController\Web\AuthenticationController;
use Movary\Service\Email\EmailSupport;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

#[CoversClass(AuthenticationController::class)]
#[AllowMockObjectsWithoutExpectations]
class AuthenticationControllerTest extends TestCase
{
    private Authentication&MockObject $authenticationMock;

    private EmailSupport&MockObject $emailSupportMock;

    private Environment&MockObject $twigMock;

    protected function setUp() : void
    {
        $this->authenticationMock = $this->createMock(Authentication::class);
        $this->emailSupportMock = $this->createMock(EmailSupport::class);
        $this->twigMock = $this->createMock(Environment::class);
    }

    public function testRenderLoginPageProvidesAllLoginPageData() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getGetParameters')->willReturn([
            'redirect' => '/users/alice',
            'password-change' => 'success',
            'password-reset' => 'success',
        ]);
        $this->emailSupportMock->method('isPasswordResetAvailable')->willReturn(true);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/login.html.twig', [
                'redirect' => '/users/alice',
                'passwordChanged' => true,
                'passwordResetAvailable' => true,
                'registrationEnabled' => true,
                'defaultEmail' => 'alice@example.com',
                'defaultPassword' => 'password',
            ])
            ->willReturn('login page');

        $subject = new AuthenticationController(
            $this->twigMock,
            $this->emailSupportMock,
            true,
            'alice@example.com',
            'password',
            $this->authenticationMock,
        );

        $response = $subject->renderLoginPage($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('login page', $response->getBody());
    }

    public function testRenderLoginPageUsesDefaultsForOptionalData() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getGetParameters')->willReturn([]);
        $this->emailSupportMock->method('isPasswordResetAvailable')->willReturn(false);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/login.html.twig', [
                'redirect' => false,
                'passwordChanged' => false,
                'passwordResetAvailable' => false,
                'registrationEnabled' => false,
                'defaultEmail' => null,
                'defaultPassword' => null,
            ])
            ->willReturn('login page');

        $subject = new AuthenticationController(
            $this->twigMock,
            $this->emailSupportMock,
            false,
            null,
            null,
            $this->authenticationMock,
        );

        $response = $subject->renderLoginPage($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('login page', $response->getBody());
    }

    public function testLoginCreatesWebSession() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn('{"email":"user@example.com","password":"password"}');
        $request->method('getUserAgent')->willReturn('agent');
        $this->authenticationMock
            ->expects(self::once())
            ->method('login')
            ->with('user@example.com', 'password', false, 'Movary Web', 'agent', null);

        $response = $this->createSubject()->login($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
    }

    public function testLoginReturnsRetryAfterWhenRateLimited() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn('{"email":"user@example.com","password":"password"}');
        $request->method('getUserAgent')->willReturn('agent');
        $this->authenticationMock
            ->method('login')
            ->willThrowException(new LoginAttemptLimitReached(60));

        $response = $this->createSubject()->login($request);

        self::assertEquals(StatusCode::createTooManyRequests(), $response->getStatusCode());
        self::assertSame('{"error":"InvalidCredentials","message":"Invalid credentials"}', $response->getBody());
        self::assertSame(
            ['Content-Type: application/json', 'Retry-After: 60', 'Cache-Control: private, no-cache'],
            array_map(static fn($header) => (string)$header, $response->getHeaders()),
        );
    }

    private function createSubject() : AuthenticationController
    {
        return new AuthenticationController(
            $this->twigMock,
            $this->emailSupportMock,
            false,
            null,
            null,
            $this->authenticationMock,
        );
    }
}
