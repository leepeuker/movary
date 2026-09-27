<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Service\Email\SmtpConfigFactory;
use Movary\Service\ServerSettings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\Email\SmtpConfigFactory::class)]
#[AllowMockObjectsWithoutExpectations]
class SmtpConfigFactoryTest extends TestCase
{
    private ServerSettings|MockObject $serverSettingsMock;

    private SmtpConfigFactory $subject;

    public function setUp() : void
    {
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->subject = new SmtpConfigFactory($this->serverSettingsMock);
    }

    public function testCreateUsesSubmittedValuesAndFallsBackToStoredPassword() : void
    {
        $this->configureEnvironmentOwnership(false);
        $this->serverSettingsMock->method('getSmtpPassword')->willReturn('stored-secret');

        $result = $this->subject->create([
            'smtpHost' => 'smtp.example.com',
            'smtpPort' => 587,
            'smtpFromAddress' => 'sender@example.com',
            'smtpEncryption' => 'tls',
            'smtpWithAuthentication' => true,
            'smtpUser' => 'submitted-user',
        ]);

        self::assertSame('smtp.example.com', $result->getHost());
        self::assertSame(587, $result->getPort());
        self::assertSame('sender@example.com', $result->getFromAddress());
        self::assertSame('tls', $result->getEncryption());
        self::assertTrue($result->isWithAuthentication());
        self::assertSame('submitted-user', $result->getUser());
        self::assertSame('stored-secret', $result->getPassword());
    }

    public function testCreatePrioritizesEnvironmentValues() : void
    {
        $this->configureEnvironmentOwnership(true);
        $this->serverSettingsMock->method('getSmtpHost')->willReturn('environment.example.com');
        $this->serverSettingsMock->method('getSmtpPort')->willReturn(465);
        $this->serverSettingsMock->method('getFromAddress')->willReturn('environment@example.com');
        $this->serverSettingsMock->method('getSmtpEncryption')->willReturn('ssl');
        $this->serverSettingsMock->method('getSmtpWithAuthentication')->willReturn(true);
        $this->serverSettingsMock->method('getSmtpUser')->willReturn('environment-user');
        $this->serverSettingsMock->method('getSmtpPassword')->willReturn('environment-secret');

        $result = $this->subject->create([
            'smtpHost' => '',
            'smtpPort' => 0,
            'smtpFromAddress' => 'invalid',
            'smtpEncryption' => 'tsl',
            'smtpWithAuthentication' => false,
            'smtpUser' => '',
            'smtpPassword' => '',
        ]);

        self::assertSame('environment.example.com', $result->getHost());
        self::assertSame(465, $result->getPort());
        self::assertSame('environment@example.com', $result->getFromAddress());
        self::assertSame('ssl', $result->getEncryption());
        self::assertTrue($result->isWithAuthentication());
        self::assertSame('environment-user', $result->getUser());
        self::assertSame('environment-secret', $result->getPassword());
    }

    private function configureEnvironmentOwnership(bool $isEnvironmentOwned) : void
    {
        $this->serverSettingsMock->method('isSmtpHostSetInEnvironment')->willReturn($isEnvironmentOwned);
        $this->serverSettingsMock->method('isSmtpPortSetInEnvironment')->willReturn($isEnvironmentOwned);
        $this->serverSettingsMock->method('isSmtpFromAddressSetInEnvironment')->willReturn($isEnvironmentOwned);
        $this->serverSettingsMock->method('isSmtpEncryptionSetInEnvironment')->willReturn($isEnvironmentOwned);
        $this->serverSettingsMock->method('isSmtpWithAuthenticationSetInEnvironment')->willReturn($isEnvironmentOwned);
        $this->serverSettingsMock->method('isSmtpUserSetInEnvironment')->willReturn($isEnvironmentOwned);
        $this->serverSettingsMock->method('isSmtpPasswordSetInEnvironment')->willReturn($isEnvironmentOwned);
    }
}
