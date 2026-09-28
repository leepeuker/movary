<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

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
    private EmailSupport&MockObject $emailSupportMock;

    private Environment&MockObject $twigMock;

    protected function setUp() : void
    {
        $this->emailSupportMock = $this->createMock(EmailSupport::class);
        $this->twigMock = $this->createMock(Environment::class);
    }

    public function testRenderLoginPageProvidesAllLoginPageData() : void
    {
        $request = $this->createMock(Request::class);
        $request->method('getGetParameters')->willReturn([
            'redirect' => '/users/alice',
            'password-reset' => 'success',
        ]);
        $this->emailSupportMock->method('isPasswordResetAvailable')->willReturn(true);
        $this->twigMock
            ->expects(self::once())
            ->method('render')
            ->with('page/login.html.twig', [
                'redirect' => '/users/alice',
                'passwordResetSuccessful' => 'success',
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
                'passwordResetSuccessful' => null,
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
        );

        $response = $subject->renderLoginPage($request);

        self::assertEquals(StatusCode::createOk(), $response->getStatusCode());
        self::assertSame('login page', $response->getBody());
    }
}
