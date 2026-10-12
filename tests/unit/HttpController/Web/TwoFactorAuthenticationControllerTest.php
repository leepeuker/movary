<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\Service\CurrentWebUser;
use Movary\Domain\User\ValueObject\AuthenticatedUser;
use Movary\Domain\User\ValueObject\CredentialType;
use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\Service\TwoFactorAuthenticationFactory;
use Movary\HttpController\Web\TwoFactorAuthenticationController;
use Movary\Service\FlashMessage\FlashMessage;
use Movary\Service\FlashMessage\FlashMessageService;
use Movary\ValueObject\Http\Request;
use OTPHP\TOTP;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TwoFactorAuthenticationController::class)]
#[AllowMockObjectsWithoutExpectations]
class TwoFactorAuthenticationControllerTest extends TestCase
{
    public function testCreateTotpUriReturnsSensitiveProvisioningData() : void
    {
        $totp = TOTP::createFromSecret('JBSWY3DPEHPK3PXP');
        $totp->setLabel('movary');
        $totp->setIssuer('Movary');
        $factory = $this->createMock(TwoFactorAuthenticationFactory::class);
        $factory->expects(self::once())->method('createTotp')->with('movary')->willReturn($totp);
        $currentWebUser = $this->createMock(CurrentWebUser::class);
        $user = $this->createMock(\Movary\Domain\User\UserEntity::class);
        $user->method('getName')->willReturn('movary');
        $currentWebUser->method('requireUser')->willReturn($user);
        $subject = new TwoFactorAuthenticationController(
            $this->createMock(Authentication::class),
            $this->createMock(TwoFactorAuthenticationApi::class),
            $factory,
            $this->createMock(FlashMessageService::class),
            $currentWebUser,
        );

        $response = $subject->createTotpUri();

        self::assertSame(
            ['uri' => $totp->getProvisioningUri(), 'secret' => 'JBSWY3DPEHPK3PXP'],
            json_decode((string)$response->getBody(), true),
        );
        $this->assertSensitiveHeaders($response->getHeaders(), contentTypeJson: true);
    }

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
            $this->createMock(CurrentWebUser::class),
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
            ->with(42, '012345', 'otpauth://totp/test')
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
            $this->createMock(CurrentWebUser::class),
        );
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn('{"input":"012345","uri":"otpauth://totp/test"}');

        $response = $subject->enableTOTP($request);

        self::assertSame(200, $response->getStatusCode()->getCode());
        $this->assertSensitiveHeaders($response->getHeaders());
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
            $this->createMock(CurrentWebUser::class),
        );
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn('{"input":"123456","uri":"otpauth://totp/test"}');

        $response = $subject->enableTOTP($request);

        self::assertSame(400, $response->getStatusCode()->getCode());
        $this->assertSensitiveHeaders($response->getHeaders());
    }

    private function assertSensitiveHeaders(array $headers, bool $contentTypeJson = false) : void
    {
        $headers = array_map('strval', $headers);

        self::assertContains('Cache-Control: no-store', $headers);
        self::assertContains('Pragma: no-cache', $headers);
        self::assertNotContains('Cache-Control: private, no-cache', $headers);
        if ($contentTypeJson === true) {
            self::assertContains('Content-Type: application/json', $headers);
        }
    }
}
