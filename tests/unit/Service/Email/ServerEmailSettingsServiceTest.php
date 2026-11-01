<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Movary\Domain\User\Service\PasswordResetTokenService;
use Movary\Service\Email\EmailSettingsConflict;
use Movary\Service\Email\InvalidSmtpConfigException;
use Movary\Service\Email\ServerEmailSettingsService;
use Movary\Service\Email\SmtpConfigFactory;
use Movary\Service\ServerSettings;
use Movary\Util\File;
use Movary\ValueObject\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ServerEmailSettingsService::class)]
#[CoversClass(ServerSettings::class)]
class ServerEmailSettingsServiceTest extends TestCase
{
    private Connection $connection;

    protected function setUp() : void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement('CREATE TABLE server_setting (`key` VARCHAR(255), value TEXT)');
    }

    public function testPartialUpdatePreservesCredentialsAndDisablingAuthenticationClearsThem() : void
    {
        [$service, $settings] = $this->service();
        $service->update($this->configuration());
        $service->update(['smtpPort' => 465]);

        self::assertSame(465, $settings->getSmtpPort());
        self::assertSame('secret-user', $settings->getSmtpUser());
        self::assertSame('secret-password', $settings->getSmtpPassword());
        self::assertTrue($settings->isEmailEnabled());

        $service->update(['smtpWithAuthentication' => false]);
        self::assertNull($settings->getSmtpUser());
        self::assertNull($settings->getSmtpPassword());
    }

    public function testInvalidUpdateLeavesEverySettingUntouched() : void
    {
        [$service, $settings] = $this->service();
        $service->update($this->configuration());
        try {
            $service->update(['smtpHost' => 'changed.example.com', 'smtpPort' => 0]);
            self::fail('Expected invalid SMTP configuration.');
        } catch (InvalidSmtpConfigException) {
            self::assertSame('smtp.example.com', $settings->getSmtpHost());
            self::assertSame(587, $settings->getSmtpPort());
        }
    }

    public function testCanConfigureSmtpWhileEmailIsDisabled() : void
    {
        [$service, $settings] = $this->service();
        $service->update(array_replace($this->configuration(), ['emailEnabled' => false]));

        self::assertFalse($settings->isEmailEnabled());
        self::assertSame('smtp.example.com', $settings->getSmtpHost());
    }

    public function testDisableAndDeleteInvalidatePasswordResetTokens() : void
    {
        $tokens = $this->createMock(PasswordResetTokenService::class);
        $tokens->expects(self::exactly(3))->method('deleteAllTokens');
        [$service, $settings] = $this->service(tokens: $tokens);
        $service->update($this->configuration());
        $service->update(['emailEnabled' => false]);
        self::assertSame('smtp.example.com', $settings->getSmtpHost());

        $service->delete();
        $service->delete();
        self::assertFalse($settings->isEmailEnabled());
        self::assertNull($settings->getSmtpHost());
        self::assertNull($settings->getSmtpPort());
        self::assertNull($settings->getFromAddress());
        self::assertNull($settings->getSmtpEncryption());
        self::assertFalse($settings->getSmtpWithAuthentication());
        self::assertNull($settings->getSmtpUser());
        self::assertNull($settings->getSmtpPassword());
    }

    #[DataProvider('environmentSettings')]
    public function testDeleteRejectsEveryEnvironmentControlledField(string $key, string $field) : void
    {
        [$service] = $this->service([$key => '1']);
        self::assertSame([$field], $service->getEnvironmentControlledFields());
        $this->expectException(EmailSettingsConflict::class);
        $service->delete();
    }

    /** @return iterable<string, array{string, string}> */
    public static function environmentSettings() : iterable
    {
        yield 'enabled' => ['EMAIL_ENABLED', 'emailEnabled'];
        yield 'host' => ['SMTP_HOST', 'smtpHost'];
        yield 'port' => ['SMTP_PORT', 'smtpPort'];
        yield 'from address' => ['SMTP_FROM_ADDRESS', 'smtpFromAddress'];
        yield 'encryption' => ['SMTP_ENCRYPTION', 'smtpEncryption'];
        yield 'authentication' => ['SMTP_WITH_AUTH', 'smtpWithAuthentication'];
        yield 'user' => ['SMTP_USER', 'smtpUser'];
        yield 'password' => ['SMTP_PASSWORD', 'smtpPassword'];
    }

    public function testRequiredVerificationRejectsDisableAndDeleteWithoutChangingConfiguration() : void
    {
        [$service, $settings] = $this->service(['EMAIL_VERIFICATION_REQUIRED' => '1']);
        $service->update($this->configuration());
        foreach (['disable', 'delete'] as $operation) {
            try {
                $operation === 'delete' ? $service->delete() : $service->update(['emailEnabled' => false]);
                self::fail('Expected email verification conflict.');
            } catch (EmailSettingsConflict) {
                self::assertTrue($settings->isEmailEnabled());
                self::assertSame('secret-password', $settings->getSmtpPassword());
            }
        }
    }

    public function testFailedDeleteRollsBackSettings() : void
    {
        $tokens = $this->createMock(PasswordResetTokenService::class);
        $tokens->expects(self::once())->method('deleteAllTokens')->willThrowException(new RuntimeException('failure'));
        [$service, $settings] = $this->service(tokens: $tokens);
        $service->update($this->configuration());
        try {
            $service->delete();
            self::fail('Expected failure.');
        } catch (RuntimeException) {
            self::assertTrue($settings->isEmailEnabled());
            self::assertSame('secret-password', $settings->getSmtpPassword());
        }
    }

    public function testFailedDisableRollsBackUpdatedSmtpConfiguration() : void
    {
        $tokens = $this->createMock(PasswordResetTokenService::class);
        $tokens->expects(self::once())->method('deleteAllTokens')->willThrowException(new RuntimeException('failure'));
        [$service, $settings] = $this->service(tokens: $tokens);
        $service->update($this->configuration());
        try {
            $service->update(['emailEnabled' => false, 'smtpHost' => 'changed.example.com']);
            self::fail('Expected failure.');
        } catch (RuntimeException) {
            self::assertTrue($settings->isEmailEnabled());
            self::assertSame('smtp.example.com', $settings->getSmtpHost());
        }
    }

    /** @param array<string, string> $environment
     * @return array{ServerEmailSettingsService, ServerSettings}
     */
    private function service(array $environment = [], ?PasswordResetTokenService $tokens = null) : array
    {
        $config = new Config($this->createStub(File::class), $environment);
        $settings = new ServerSettings($config, $this->connection, new File());

        return [new ServerEmailSettingsService(
            $settings,
            new SmtpConfigFactory($settings),
            $tokens ?? $this->createStub(PasswordResetTokenService::class),
            $config,
            $this->connection,
        ), $settings];
    }

    /** @return array<string, mixed> */
    private function configuration() : array
    {
        return [
            'emailEnabled' => true,
            'smtpHost' => 'smtp.example.com',
            'smtpPort' => 587,
            'smtpFromAddress' => 'sender@example.com',
            'smtpEncryption' => 'tls',
            'smtpWithAuthentication' => true,
            'smtpUser' => 'secret-user',
            'smtpPassword' => 'secret-password',
        ];
    }
}
