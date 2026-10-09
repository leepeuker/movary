<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\Service\TwoFactorAuthenticationFactory;
use Movary\HttpController\Web\TwoFactorAuthenticationController;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TwoFactorAuthenticationController::class)]
#[AllowMockObjectsWithoutExpectations]
class TwoFactorAuthenticationControllerTest extends TestCase
{
    public function testDisableTotpAddsDisabledMessage() : void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $twoFactorApi = $this->createMock(TwoFactorAuthenticationApi::class);
        $twoFactorApi->expects(self::once())->method('deleteTotp')->with(42);
        $flashMessageService = $this->createMock(FlashMessageService::class);
        $flashMessageService
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::TWO_FACTOR_AUTHENTICATION_DISABLED);
        $subject = new TwoFactorAuthenticationController(
            $authentication,
            $twoFactorApi,
            $this->createMock(TwoFactorAuthenticationFactory::class),
            $flashMessageService,
            $this->createMock(UserApi::class),
        );

        self::assertSame(200, $subject->disableTOTP()->getStatusCode()->getCode());
    }

    public function testEnableTotpAddsEnabledMessageForValidCode() : void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $twoFactorApi = $this->createMock(TwoFactorAuthenticationApi::class);
        $twoFactorApi
            ->expects(self::once())
            ->method('verifyTotpUri')
            ->with(42, 123456, 'otpauth://totp/test')
            ->willReturn(true);
        $twoFactorApi->expects(self::once())->method('updateTotpUri')->with(42, 'otpauth://totp/test');
        $flashMessageService = $this->createMock(FlashMessageService::class);
        $flashMessageService
            ->expects(self::once())
            ->method('add')
            ->with(FlashMessage::TWO_FACTOR_AUTHENTICATION_ENABLED);
        $subject = new TwoFactorAuthenticationController(
            $authentication,
            $twoFactorApi,
            $this->createMock(TwoFactorAuthenticationFactory::class),
            $flashMessageService,
            $this->createMock(UserApi::class),
        );
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn('{"input":123456,"uri":"otpauth://totp/test"}');

        self::assertSame(200, $subject->enableTOTP($request)->getStatusCode()->getCode());
    }

    public function testEnableTotpDoesNotAddMessageForInvalidCode() : void
    {
        $authentication = $this->createMock(Authentication::class);
        $authentication->expects(self::once())->method('requireWebSession')->willReturn(AuthenticatedUser::create(42, CredentialType::WEB_SESSION));
        $twoFactorApi = $this->createMock(TwoFactorAuthenticationApi::class);
        $twoFactorApi->expects(self::once())->method('verifyTotpUri')->willReturn(false);
        $twoFactorApi->expects(self::never())->method('updateTotpUri');
        $flashMessageService = $this->createMock(FlashMessageService::class);
        $flashMessageService->expects(self::never())->method('add');
        $subject = new TwoFactorAuthenticationController(
            $authentication,
            $twoFactorApi,
            $this->createMock(TwoFactorAuthenticationFactory::class),
            $flashMessageService,
            $this->createMock(UserApi::class),
        );
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn('{"input":123456,"uri":"otpauth://totp/test"}');

        self::assertSame(400, $subject->enableTOTP($request)->getStatusCode()->getCode());
    }
}
