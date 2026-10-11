<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\TwoFactorAuthenticationApi;
use Movary\Domain\User\Service\TwoFactorAuthenticationFactory;
use Movary\Domain\User\UserApi;
use OTPHP\OTPInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TwoFactorAuthenticationApi::class)]
class TwoFactorAuthenticationApiTest extends TestCase
{
    public function testVerifyTotpUriPreservesLeadingZero() : void
    {
        $userApi = $this->createStub(UserApi::class);
        $factory = $this->createMock(TwoFactorAuthenticationFactory::class);
        $totp = $this->createMock(OTPInterface::class);
        $factory
            ->expects(self::once())
            ->method('createOtpFromProvisioningUri')
            ->with('otpauth://totp/test')
            ->willReturn($totp);
        $totp->expects(self::once())->method('verify')->with('012345')->willReturn(true);

        $subject = new TwoFactorAuthenticationApi($userApi, $factory);

        self::assertTrue($subject->verifyTotpUri(42, '012345', 'otpauth://totp/test'));
    }

    #[DataProvider('provideInvalidTotpCodes')]
    public function testVerifyTotpUriRejectsInvalidCodeFormat(string $code) : void
    {
        $factory = $this->createMock(TwoFactorAuthenticationFactory::class);
        $factory->expects(self::never())->method('createOtpFromProvisioningUri');
        $subject = new TwoFactorAuthenticationApi($this->createStub(UserApi::class), $factory);

        self::assertFalse($subject->verifyTotpUri(42, $code, 'otpauth://totp/test'));
    }

    public static function provideInvalidTotpCodes() : array
    {
        return [
            'too short' => ['12345'],
            'too long' => ['1234567'],
            'non-digit' => ['12345a'],
        ];
    }
}
