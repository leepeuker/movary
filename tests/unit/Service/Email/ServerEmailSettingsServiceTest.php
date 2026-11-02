<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Email;

use Doctrine\DBAL\Connection;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ServerEmailSettingsService::class)]
class ServerEmailSettingsServiceTest extends TestCase
{
    private Connection&MockObject $connection;

    private ServerSettings&MockObject $settings;

    private SmtpConfigFactory&MockObject $smtpConfigFactory;

    protected function setUp() : void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->settings = $this->createMock(ServerSettings::class);
        $this->smtpConfigFactory = $this->createMock(SmtpConfigFactory::class);
    }

    public function testPartialUpdatePreservesCredentials() : void
    {
        $this->settings->method('isEmailEnabled')->willReturn(true);
        $this->settings->method('getSmtpWithAuthentication')->willReturn(true);
        $this->settings->expects(self::once())->method('setSmtpPort')->with(465);
        $this->settings->expects(self::never())->method('setSmtpUser');
        $this->settings->expects(self::never())->method('setSmtpPassword');
        $this->settings->expects(self::never())->method('clearSmtpAuthenticationCredentials');
        $this->settings->expects(self::once())->method('setEmailEnabled')->with(true);
        $this->smtpConfigFactory->expects(self::once())->method('create')->with(['smtpPort' => 465]);
        $this->expectTransaction();

        $this->service()->update(['smtpPort' => 465]);
    }

    public function testDisablingAuthenticationClearsCredentials() : void
    {
        $this->settings->method('isEmailEnabled')->willReturn(true);
        $this->settings->expects(self::once())->method('setSmtpFromWithAuthentication')->with(false);
        $this->settings->expects(self::once())->method('clearSmtpAuthenticationCredentials');
        $this->settings->expects(self::never())->method('setSmtpUser');
        $this->settings->expects(self::never())->method('setSmtpPassword');
        $this->smtpConfigFactory->expects(self::once())->method('create')->with(['smtpWithAuthentication' => false]);
        $this->expectTransaction();

        $this->service()->update(['smtpWithAuthentication' => false]);
    }

    public function testInvalidUpdateLeavesEverySettingUntouched() : void
    {
        $request = ['smtpHost' => 'changed.example.com', 'smtpPort' => 0];
        $this->smtpConfigFactory->expects(self::once())->method('create')->with($request)
            ->willThrowException(new InvalidSmtpConfigException('Invalid port.'));
        $this->connection->expects(self::never())->method('transactional');
        $this->settings->expects(self::never())->method('setSmtpHost');
        $this->settings->expects(self::never())->method('setSmtpPort');
        $this->settings->expects(self::never())->method('setEmailEnabled');
        $this->expectException(InvalidSmtpConfigException::class);

        $this->service()->update($request);
    }

    public function testCanConfigureSmtpWhileEmailIsDisabled() : void
    {
        $request = ['emailEnabled' => false, 'smtpHost' => 'smtp.example.com'];
        $this->settings->expects(self::once())->method('setSmtpHost')->with('smtp.example.com');
        $this->settings->expects(self::once())->method('setEmailEnabled')->with(false);
        $this->smtpConfigFactory->expects(self::once())->method('create')->with($request);
        $this->expectTransaction();

        $this->service()->update($request);
    }

    public function testDisableInvalidatesPasswordResetTokens() : void
    {
        $tokens = $this->createMock(PasswordResetTokenService::class);
        $tokens->expects(self::once())->method('deleteAllTokens');
        $this->settings->expects(self::once())->method('setEmailEnabled')->with(false);
        $this->settings->expects(self::never())->method('clearSmtpConfiguration');
        $this->smtpConfigFactory->expects(self::never())->method('create');
        $this->expectTransaction();

        $this->service(tokens: $tokens)->update(['emailEnabled' => false]);
    }

    public function testDeleteClearsSettingsAndInvalidatesPasswordResetTokensOnEveryCall() : void
    {
        $tokens = $this->createMock(PasswordResetTokenService::class);
        $tokens->expects(self::exactly(2))->method('deleteAllTokens');
        $this->settings->expects(self::exactly(2))->method('clearSmtpConfiguration');
        $this->settings->expects(self::exactly(2))->method('setEmailEnabled')->with(false);
        $this->smtpConfigFactory->expects(self::never())->method('create');
        $this->expectTransaction(2);
        $service = $this->service(tokens: $tokens);

        $service->delete();
        $service->delete();
    }

    #[DataProvider('environmentSettings')]
    public function testDeleteRejectsEveryEnvironmentControlledField(string $method, string $field) : void
    {
        $this->settings->method($method)->willReturn(true);
        $this->settings->expects(self::never())->method('clearSmtpConfiguration');
        $this->connection->expects(self::never())->method('transactional');
        $this->smtpConfigFactory->expects(self::never())->method('create');
        $service = $this->service();
        self::assertSame([$field], $service->getEnvironmentControlledFields());
        $this->expectException(EmailSettingsConflict::class);

        $service->delete();
    }

    /** @return iterable<string, array{string, string}> */
    public static function environmentSettings() : iterable
    {
        yield 'enabled' => ['isEmailEnabledSetInEnvironment', 'emailEnabled'];
        yield 'host' => ['isSmtpHostSetInEnvironment', 'smtpHost'];
        yield 'port' => ['isSmtpPortSetInEnvironment', 'smtpPort'];
        yield 'from address' => ['isSmtpFromAddressSetInEnvironment', 'smtpFromAddress'];
        yield 'encryption' => ['isSmtpEncryptionSetInEnvironment', 'smtpEncryption'];
        yield 'authentication' => ['isSmtpWithAuthenticationSetInEnvironment', 'smtpWithAuthentication'];
        yield 'user' => ['isSmtpUserSetInEnvironment', 'smtpUser'];
        yield 'password' => ['isSmtpPasswordSetInEnvironment', 'smtpPassword'];
    }

    #[DataProvider('conflictingOperations')]
    public function testRequiredVerificationRejectsDisableAndDeleteWithoutChangingConfiguration(string $operation) : void
    {
        $this->connection->expects(self::never())->method('transactional');
        $this->settings->expects(self::never())->method('clearSmtpConfiguration');
        $this->settings->expects(self::never())->method('setEmailEnabled');
        $this->smtpConfigFactory->expects(self::never())->method('create');
        $service = $this->service(['EMAIL_VERIFICATION_REQUIRED' => '1']);
        $this->expectException(EmailSettingsConflict::class);

        $operation === 'delete' ? $service->delete() : $service->update(['emailEnabled' => false]);
    }

    /** @return iterable<string, array{string}> */
    public static function conflictingOperations() : iterable
    {
        yield 'disable' => ['disable'];
        yield 'delete' => ['delete'];
    }

    #[DataProvider('conflictingOperations')]
    public function testTokenFailurePropagatesThroughTransaction(string $operation) : void
    {
        $failure = new RuntimeException('failure');
        $tokens = $this->createMock(PasswordResetTokenService::class);
        $tokens->expects(self::once())->method('deleteAllTokens')->willThrowException($failure);
        $this->settings->expects(self::once())->method('setEmailEnabled')->with(false);
        $this->settings->expects($operation === 'delete' ? self::once() : self::never())->method('clearSmtpConfiguration');
        $this->smtpConfigFactory->expects(self::never())->method('create');
        $this->expectTransaction();
        $service = $this->service(tokens: $tokens);
        $this->expectExceptionObject($failure);

        $operation === 'delete' ? $service->delete() : $service->update(['emailEnabled' => false]);
    }

    private function expectTransaction(int $count = 1) : void
    {
        $this->connection->expects(self::exactly($count))->method('transactional')
            ->willReturnCallback(function (\Closure $callback) : void {
                $callback($this->connection);
            });
    }

    /** @param array<string, string> $environment */
    private function service(array $environment = [], ?PasswordResetTokenService $tokens = null) : ServerEmailSettingsService
    {
        return new ServerEmailSettingsService(
            $this->settings,
            $this->smtpConfigFactory,
            $tokens ?? $this->createStub(PasswordResetTokenService::class),
            new Config($this->createStub(File::class), $environment),
            $this->connection,
        );
    }
}
