<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Service\EmailVerificationPolicy;
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

        self::assertFalse((new EmailVerificationPolicy($config))->isRequired());
    }

    public function testCanRequireVerification() : void
    {
        $config = $this->createStub(Config::class);
        $config->method('getAsBool')->willReturn(true);

        self::assertTrue((new EmailVerificationPolicy($config))->isRequired());
    }
}
