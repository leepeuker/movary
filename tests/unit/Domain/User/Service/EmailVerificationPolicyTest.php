<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Exception\InvalidEmailVerificationConfiguration;
use Movary\Domain\User\Service\EmailVerificationPolicy;
use Movary\Service\Email\EmailSupport;
use Movary\ValueObject\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailVerificationPolicy::class)]
class EmailVerificationPolicyTest extends TestCase
{
    public function testDefaultsToOptional() : void
    {
        $config = $this->createMock(Config::class);
        $config
            ->expects(self::once())
            ->method('getAsBool')
            ->with('EMAIL_VERIFICATION_REQUIRED', false)
            ->willReturn(false);

        $emailSupport = $this->createMock(EmailSupport::class);
        $emailSupport->expects(self::never())->method('isEmailVerificationAvailable');

        self::assertFalse((new EmailVerificationPolicy($config, $emailSupport))->isRequired());
    }

    public function testCanRequireVerification() : void
    {
        $config = $this->createStub(Config::class);
        $config->method('getAsBool')->willReturn(true);
        $emailSupport = $this->createStub(EmailSupport::class);
        $emailSupport->method('isEmailVerificationAvailable')->willReturn(true);

        self::assertTrue((new EmailVerificationPolicy($config, $emailSupport))->isRequired());
    }

    public function testRejectsRequiredVerificationWhenEmailVerificationIsUnavailable() : void
    {
        $config = $this->createStub(Config::class);
        $config->method('getAsBool')->willReturn(true);
        $emailSupport = $this->createStub(EmailSupport::class);
        $emailSupport->method('isEmailVerificationAvailable')->willReturn(false);

        $this->expectException(InvalidEmailVerificationConfiguration::class);
        $this->expectExceptionMessage('EMAIL_VERIFICATION_REQUIRED requires enabled email support');

        (new EmailVerificationPolicy($config, $emailSupport))->isRequired();
    }
}
