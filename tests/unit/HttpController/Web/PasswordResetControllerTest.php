<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\PasswordResetRequestService;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\HttpController\Web\PasswordResetController;
use Movary\Service\ApplicationUrlService;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
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

#[CoversClass(PasswordResetController::class)]
#[AllowMockObjectsWithoutExpectations]
class PasswordResetControllerTest extends TestCase
{
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private PasswordResetRequestService|MockObject $requestServiceMock;

    private FlashMessageService|MockObject $flashMessageServiceMock;

    private PasswordResetController $subject;

    private PasswordResetTokenService|MockObject $tokenServiceMock;

    private Environment|MockObject $twigMock;

    protected function setUp() : void
    {
        $this->twigMock = $this->createMock(Environment::class);
        $this->requestServiceMock = $this->createMock(PasswordResetRequestService::class);
        $this->tokenServiceMock = $this->createMock(PasswordResetTokenService::class);
        $this->flashMessageServiceMock = $this->createMock(FlashMessageService::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = new PasswordResetController(
            $this->twigMock,
            $this->requestServiceMock,
            $this->tokenServiceMock,
            $this->flashMessageServiceMock,
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
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::PASSWORD_RESET_REQUESTED);
        $this->applicationUrlServiceMock
            ->expects(self::once())
            ->method('createApplicationUrl')
            ->with(self::callback(static fn(RelativeUrl $url) => (string)$url === '/forgot-password'))
            ->willReturn('/movary/forgot-password');

        $response = $this->subject->requestReset($request);

        self::assertEquals(StatusCode::createSeeOther(), $response->getStatusCode());
        self::assertSame('Location: /movary/forgot-password', (string)$response->getHeaders()[0]);
    }

    public function testRenderRequestPageConsumesConfirmation() : void
    {
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('consume')
            ->with(FlashMessage::PASSWORD_RESET_REQUESTED)
            ->willReturn(true);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with(
                'page/password-reset-request.html.twig',
                ['requestSubmitted' => true],
            )
            ->willReturn('confirmation');

        $response = $this->subject->renderRequestPage();

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('confirmation', $response->getBody());
    }

    public function testRenderRequestPageDoesNotShowConfirmationWithoutMessage() : void
    {
        $this->flashMessageServiceMock
            ->expects(self::once())
            ->method('consume')
            ->with(FlashMessage::PASSWORD_RESET_REQUESTED)
            ->willReturn(false);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with(
                'page/password-reset-request.html.twig',
                ['requestSubmitted' => false],
            );

        $this->subject->renderRequestPage();
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
        $this->assertSensitiveResetHeaders($response);
    }

    public function testRenderResetPageShowsErrorForInvalidToken() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getGetParameters')->willReturn(['token' => 'invalid-token']);
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(null);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['token'] === 'invalid-token' && $data['tokenValid'] === false,
            ));

        $response = $this->subject->renderResetPage($request);

        $this->assertSensitiveResetHeaders($response);
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

        $response = $this->subject->resetPassword($request);

        $this->assertSensitiveResetHeaders($response);
    }

    public function testResetPasswordRejectsMissingFields() : void
    {
        $request = $this->createResetRequest('', '');
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->tokenServiceMock->expects(self::never())->method('resetPassword');
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['missingFormData'] === true,
            ));

        $response = $this->subject->resetPassword($request);

        $this->assertSensitiveResetHeaders($response);
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

        $response = $this->subject->resetPassword($request);

        $this->assertSensitiveResetHeaders($response);
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

        $response = $this->subject->resetPassword($request);

        $this->assertSensitiveResetHeaders($response);
    }

    public function testResetPasswordShowsInvalidTokenWhenRepositoryUpdateFails() : void
    {
        $request = $this->createResetRequest();
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->tokenServiceMock->method('resetPassword')->willReturn(false);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['token'] === '' && $data['tokenValid'] === false,
            ));

        $response = $this->subject->resetPassword($request);

        $this->assertSensitiveResetHeaders($response);
    }

    public function testResetPasswordShowsGenericErrorForUnexpectedFailure() : void
    {
        $request = $this->createResetRequest();
        $this->tokenServiceMock->method('findUserIdByToken')->willReturn(12);
        $this->tokenServiceMock->method('resetPassword')->willThrowException(new \RuntimeException('failure'));
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/password-reset.html.twig', self::callback(
                static fn(array $data) => $data['token'] === 'reset-token' && $data['genericError'] === true,
            ));

        $response = $this->subject->resetPassword($request);

        $this->assertSensitiveResetHeaders($response);
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
        $this->assertSensitiveResetHeaders($response);
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

    private function assertSensitiveResetHeaders(Response $response) : void
    {
        $headers = array_map('strval', $response->getHeaders());

        self::assertContains('Cache-Control: no-store', $headers);
        self::assertContains('Pragma: no-cache', $headers);
        self::assertContains('Referrer-Policy: no-referrer', $headers);
        self::assertNotContains('Cache-Control: private, no-cache', $headers);
    }
}
