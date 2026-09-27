<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\Service\ServerSettings;
use Movary\ValueObject\Config;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Service\ServerSettings::class)]
#[AllowMockObjectsWithoutExpectations]
class ServerSettingsTest extends TestCase
{
    private Config|MockObject $configMock;

    private Connection|MockObject $dbConnectionMock;

    private ServerSettings $subject;

    public function setUp() : void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->dbConnectionMock = $this->createMock(Connection::class);

        $this->subject = new ServerSettings($this->configMock, $this->dbConnectionMock);
    }

    public function testEnvironmentBackedSmtpValuesAreNotPersisted() : void
    {
        $smtpEnvironmentKeys = [
            'SMTP_ENCRYPTION',
            'SMTP_FROM_ADDRESS',
            'SMTP_WITH_AUTH',
            'SMTP_HOST',
            'SMTP_PASSWORD',
            'SMTP_PORT',
            'SMTP_USER',
        ];

        $this->configMock
            ->expects(self::exactly(count($smtpEnvironmentKeys)))
            ->method('getAsString')
            ->with(self::callback(static fn (string $key) : bool => in_array($key, $smtpEnvironmentKeys, true)))
            ->willReturn('configured');

        $this->dbConnectionMock->expects(self::never())->method('prepare');

        $this->subject->setSmtpEncryption('tls');
        $this->subject->setSmtpFromAddress('sender@example.com');
        $this->subject->setSmtpFromWithAuthentication(true);
        $this->subject->setSmtpHost('smtp.example.com');
        $this->subject->setSmtpPassword('password');
        $this->subject->setSmtpPort(587);
        $this->subject->setSmtpUser('user');
    }
}
