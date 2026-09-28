<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Movary\Service\ApplicationUrlService;
use Movary\Service\Email\EmailSupport;
use Movary\Service\Email\InvalidSmtpConfigException;
use Movary\Service\Email\SmtpConfig;
use Movary\Service\Email\SmtpConfigFactory;
use Movary\Service\ServerSettings;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(EmailSupport::class)]
#[AllowMockObjectsWithoutExpectations]
class EmailSupportTest extends TestCase
{
    private ApplicationUrlService|MockObject $applicationUrlServiceMock;

    private ServerSettings|MockObject $serverSettingsMock;

    private SmtpConfigFactory|MockObject $smtpConfigFactoryMock;

    private EmailSupport $subject;

    protected function setUp() : void
    {
        $this->serverSettingsMock = $this->createMock(ServerSettings::class);
        $this->smtpConfigFactoryMock = $this->createMock(SmtpConfigFactory::class);
        $this->applicationUrlServiceMock = $this->createMock(ApplicationUrlService::class);
        $this->subject = new EmailSupport(
            $this->serverSettingsMock,
            $this->smtpConfigFactoryMock,
            $this->applicationUrlServiceMock,
        );
    }

    public function testPasswordResetIsUnavailableWhenEmailSupportIsDisabled() : void
    {
        $this->serverSettingsMock->method('isEmailEnabled')->willReturn(false);
        $this->smtpConfigFactoryMock->expects(self::never())->method('create');
        $this->applicationUrlServiceMock->expects(self::never())->method('hasApplicationUrl');

        self::assertFalse($this->subject->isPasswordResetAvailable());
    }

    public function testPasswordResetIsUnavailableWhenSmtpConfigurationIsInvalid() : void
    {
        $this->serverSettingsMock->method('isEmailEnabled')->willReturn(true);
        $this->smtpConfigFactoryMock
            ->method('create')
            ->willThrowException(new InvalidSmtpConfigException('Invalid SMTP configuration.'));
        $this->applicationUrlServiceMock->expects(self::never())->method('hasApplicationUrl');

        self::assertFalse($this->subject->isPasswordResetAvailable());
    }

    public function testPasswordResetIsUnavailableWithoutApplicationUrl() : void
    {
        $this->serverSettingsMock->method('isEmailEnabled')->willReturn(true);
        $this->smtpConfigFactoryMock->method('create')->willReturn($this->createMock(SmtpConfig::class));
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(false);

        self::assertFalse($this->subject->isPasswordResetAvailable());
    }

    public function testPasswordResetIsAvailableWithCompleteConfiguration() : void
    {
        $this->serverSettingsMock->method('isEmailEnabled')->willReturn(true);
        $this->smtpConfigFactoryMock->method('create')->willReturn($this->createMock(SmtpConfig::class));
        $this->applicationUrlServiceMock->method('hasApplicationUrl')->willReturn(true);

        self::assertTrue($this->subject->isPasswordResetAvailable());
    }
}
