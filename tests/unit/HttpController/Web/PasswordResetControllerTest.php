<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\HttpController\Web\PasswordResetController;
use Movary\Service\ApplicationUrlService;
use Movary\Util\SessionWrapper;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Environment;

#[CoversClass(PasswordResetController::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetControllerTest extends TestCase
{
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private PasswordResetRequestService|MockObject $requestServiceMock;

    private SessionWrapper|MockObject $sessionWrapperMock;

    private PasswordResetController $subject;

    private PasswordResetTokenService|MockObject $tokenServiceMock;

    private Environment|MockObject $twigMock;

    protected function setUp() : void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->requestServiceMock = $this->createMock(PasswordResetRequestService::class);
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $this->sessionWrapperMock = $this->createMock(SessionWrapper::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = new PasswordResetController(
            $this->twigMock,
            $this->requestServiceMock,
            $this->tokenServiceMock,
            $this->sessionWrapperMock,
            $this->applicationUrlServiceMock,
            $this->createMock(LoggerInterface::class),
        );
    }

    public function testRequestResetAlwaysRedirectsToNeutralConfirmation() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn(['email' => ' user@example.com ']);
        $this->requestServiceMock
            ->expects(self::once())
            ->method('request')
            ->with(' user@example.com ');
        $this->sessionWrapperMock
            ->expects(self::once())
            ->method('set')
            ->with('passwordResetRequested', true);
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(static fn(RelativeUrl $url) => (string)$url === '/forgot-password'))
            ->willReturn('/movary/forgot-password');

        $response = $this->subject->requestReset($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary/forgot-password', (string)$response->getHeaders()[0]);
    }

    public function testRenderResetPageShowsFormForValidToken() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getGetParameters')->willReturn(['token' => 'reset-token']);
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('findUserIdByToken')
            ->with('reset-token')
            ->willReturn(12);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['token'] === 'reset-token' && $data['tokenValid'] === true,
            ))
            ->willReturn('reset form');

        $response = $this->subject->renderResetPage($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('reset form', $response->getBody());
    }

    public function testResetPasswordRejectsInvalidToken() : void
    {
        $request = $this->createResetRequest();
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(null);
        $this->tokenServiceMock->expects(self::never())->method('resetPassword');
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['token'] === '' && $data['tokenValid'] === false,
            ));

        $this->subject->resetPassword($request);
    }

    public function testResetPasswordRejectsMismatchedPasswords() : void
    {
        $request = $this->createResetRequest('new-password', 'different-password');
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->tokenServiceMock->expects(self::never())->method('resetPassword');
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['tokenValid'] === true
                    && $data['passwordsDoNotMatch'] === true,
            ));

        $this->subject->resetPassword($request);
    }

    public function testResetPasswordShowsPasswordPolicyError() : void
    {
        $request = $this->createResetRequest('short', 'short');
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->tokenServiceMock
            ->method('resetPassword')
            ->willThrowException(new PasswordTooShort(8));
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['minimumPasswordLength'] === 8,
            ));

        $this->subject->resetPassword($request);
    }

    public function testResetPasswordRedirectsToLoginAfterSuccess() : void
    {
        $request = $this->createResetRequest();
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->tokenServiceMock
            ->expects(self::once())
            ->method('resetPassword')
            ->with('reset-token', 'new-password')
            ->willReturn(true);
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(
                static fn(RelativeUrl $url) => (string)$url === '/login?password-reset=success',
            ))
            ->willReturn('/movary/login?password-reset=success');

        $response = $this->subject->resetPassword($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary/login?password-reset=success', (string)$response->getHeaders()[0]);
    }

    private function createResetRequest(
        string $password = 'new-password',
        string $repeatPassword = 'new-password',
    ) : Request&MockObject {
        $request = $this->createMock(Request::class);
        $request->method('getPostParameters')->willReturn([
            'token' => 'reset-token',
            'password' => $password,
            'repeatPassword' => $repeatPassword,
        ]);

        return $request;
    }
}
