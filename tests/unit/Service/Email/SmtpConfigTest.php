<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Service\Email\InvalidSmtpConfigException;
use Movary\Service\Email\SmtpConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\Email\SmtpConfig::class)]
#[CoversClass(\Movary\Service\Email\InvalidSmtpConfigException::class)]
class SmtpConfigTest extends TestCase
{
    public static function provideInvalidConfigurationData() : array
    {
        return [
            'host missing' => ['', 587, 'sender@example.com', 'tls', false, null, null, 'SMTP host must be set.'],
            'port too low' => ['smtp.example.com', 0, 'sender@example.com', 'tls', false, null, null, 'SMTP port must be between 1 and 65535.'],
            'port too high' => ['smtp.example.com', 65536, 'sender@example.com', 'tls', false, null, null, 'SMTP port must be between 1 and 65535.'],
            'sender invalid' => ['smtp.example.com', 587, 'invalid', 'tls', false, null, null, 'SMTP from address must be a valid email address.'],
            'encryption invalid' => ['smtp.example.com', 587, 'sender@example.com', 'tsl', false, null, null, 'SMTP encryption must be ssl, tls, or empty.'],
            'user missing' => ['smtp.example.com', 587, 'sender@example.com', 'tls', true, null, 'secret', 'SMTP user must be set when authentication is enabled.'],
            'password missing' => ['smtp.example.com', 587, 'sender@example.com', 'tls', true, 'user', null, 'SMTP password must be set when authentication is enabled.'],
        ];
    }

    public function testCreateNormalizesAndReturnsValidConfiguration() : void
    {
        $subject = SmtpConfig::create(
            ' smtp.example.com ',
            587,
            ' sender@example.com ',
            '',
            false,
            null,
            null,
        );

        self::assertSame('smtp.example.com', $subject->getHost());
        self::assertSame(587, $subject->getPort());
        self::assertSame('sender@example.com', $subject->getFromAddress());
        self::assertNull($subject->getEncryption());
        self::assertFalse($subject->isWithAuthentication());
        self::assertNull($subject->getUser());
        self::assertNull($subject->getPassword());
    }

    #[DataProvider('provideInvalidConfigurationData')]
    public function testCreateRejectsInvalidConfiguration(
        string $host,
        int $port,
        string $fromAddress,
        ?string $encryption,
        bool $withAuthentication,
        ?string $user,
        ?string $password,
        string $expectedMessage,
    ) : void {
        $this->expectException(InvalidSmtpConfigException::class);
        $this->expectExceptionMessage($expectedMessage);

        SmtpConfig::create($host, $port, $fromAddress, $encryption, $withAuthentication, $user, $password);
    }
}
