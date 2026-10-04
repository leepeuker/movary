<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Doctrine\DBAL\Connection;
use Movary\Service\ServerSettings;
use Movary\ValueObject\Config;
use Movary\ValueObject\Exception\ConfigNotSetException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerSettings::class)]
#[AllowMockObjectsWithoutExpectations]
class ServerSettingsTest extends TestCase
{
    private Config|MockObject $configMock;

    private Connection|MockObject $dbConnectionMock;

    private ServerSettings $subject;

    protected function setUp() : void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->dbConnectionMock = $this->createMock(Connection::class);
        $this->subject = new ServerSettings($this->configMock, $this->dbConnectionMock);
    }

    public function testEmailSupportIsDisabledByDefault() : void
    {
        $this->configMock
            ->expects(self::once())
            ->method('getAsString')
            ->with('EMAIL_ENABLED')
            ->willThrowException(ConfigNotSetException::create('EMAIL_ENABLED'));
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->with('SELECT value FROM `server_setting` WHERE `key` = ?', ['emailEnabled'])
            ->willReturn([]);

        self::assertFalse($this->subject->isEmailEnabled());
    }

    public function testEnvironmentSettingEnablesEmailSupport() : void
    {
        $this->configMock
            ->expects(self::once())
            ->method('getAsString')
            ->with('EMAIL_ENABLED')
            ->willReturn('1');
        $this->dbConnectionMock->expects(self::never())->method('fetchFirstColumn');

        self::assertTrue($this->subject->isEmailEnabled());
    }

    public function testDatabaseSettingEnablesEmailSupport() : void
    {
        $this->configMock
            ->expects(self::once())
            ->method('getAsString')
            ->with('EMAIL_ENABLED')
            ->willThrowException(ConfigNotSetException::create('EMAIL_ENABLED'));
        $this->dbConnectionMock
            ->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn(['1']);

        self::assertTrue($this->subject->isEmailEnabled());
    }

    public function testLoginAttemptSettingsUseDefaults() : void
    {
        $this->configMock
            ->expects(self::exactly(2))
            ->method('getAsString')
            ->willThrowException(ConfigNotSetException::create('setting'));
        $this->dbConnectionMock
            ->expects(self::exactly(2))
            ->method('fetchFirstColumn')
            ->willReturn([]);

        self::assertSame(5, $this->subject->getLoginAttemptLimit());
        self::assertSame(900, $this->subject->getLoginAttemptWindowInSeconds());
    }

    public function testLoginAttemptSettingsUseEnvironmentValues() : void
    {
        $this->configMock
            ->expects(self::exactly(2))
            ->method('getAsString')
            ->willReturnOnConsecutiveCalls('2', '60');
        $this->dbConnectionMock->expects(self::never())->method('fetchFirstColumn');

        self::assertSame(2, $this->subject->getLoginAttemptLimit());
        self::assertSame(60, $this->subject->getLoginAttemptWindowInSeconds());
    }

    public function testLoginAttemptSettingsUseDefaultsForNonPositiveValues() : void
    {
        $this->configMock
            ->expects(self::exactly(2))
            ->method('getAsString')
            ->willReturnOnConsecutiveCalls('0', '-1');

        self::assertSame(5, $this->subject->getLoginAttemptLimit());
        self::assertSame(900, $this->subject->getLoginAttemptWindowInSeconds());
    }
}
