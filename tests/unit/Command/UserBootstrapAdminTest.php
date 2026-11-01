<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Command;

use Movary\Command\UserBootstrapAdmin;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\UserApi;
use Movary\Util\File;
use Movary\ValueObject\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(UserBootstrapAdmin::class)]
class UserBootstrapAdminTest extends TestCase
{
    public function testDoesNothingWhenNoBootstrapSettingsAreProvided() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->expects(self::never())->method('hasUsers');
        $userApi->expects(self::never())->method('createUser');

        $tester = $this->createTester([], $userApi);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testCreatesInitialAdministrator() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->method('hasUsers')->willReturn(false);
        $userApi->expects(self::once())->method('createUser')->with(
            'admin@example.com',
            'secure-password',
            'admin',
            true,
            true,
            true,
        );

        $tester = $this->createTester($this->completeConfiguration(), $userApi);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Initial administrator created.', $tester->getDisplay());
    }

    public function testDoesNotChangeAnExistingInstallation() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->method('hasUsers')->willReturn(true);
        $userApi->expects(self::never())->method('createUser');

        $tester = $this->createTester($this->completeConfiguration(), $userApi);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testRejectsPartialConfiguration() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->method('hasUsers')->willReturn(false);
        $userApi->expects(self::never())->method('createUser');

        $tester = $this->createTester(['INITIAL_ADMIN_EMAIL' => 'admin@example.com'], $userApi);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('INITIAL_ADMIN_USERNAME', $tester->getDisplay());
    }

    public function testReadsPasswordFromFile() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->method('hasUsers')->willReturn(false);
        $userApi->expects(self::once())->method('createUser')->with(
            'admin@example.com',
            'secure-password',
            'admin',
            true,
            true,
            true,
        );
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('fileExists')->with('/run/secrets/admin_password')->willReturn(true);
        $file->expects(self::once())->method('readFile')->with('/run/secrets/admin_password')->willReturn("secure-password\n");
        $configuration = $this->completeConfiguration();
        unset($configuration['INITIAL_ADMIN_PASSWORD']);
        $configuration['INITIAL_ADMIN_PASSWORD_FILE'] = '/run/secrets/admin_password';

        $tester = $this->createTester($configuration, $userApi, $file);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testFailsWhenPasswordFileIsMissing() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->method('hasUsers')->willReturn(false);
        $userApi->expects(self::never())->method('createUser');
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('fileExists')->willReturn(false);
        $configuration = $this->completeConfiguration();
        unset($configuration['INITIAL_ADMIN_PASSWORD']);
        $configuration['INITIAL_ADMIN_PASSWORD_FILE'] = '/run/secrets/admin_password';

        $tester = $this->createTester($configuration, $userApi, $file);

        self::assertSame(Command::FAILURE, $tester->execute([]));
    }

    public function testFailsWhenPasswordDoesNotMeetPolicy() : void
    {
        $userApi = $this->createMock(UserApi::class);
        $userApi->method('hasUsers')->willReturn(false);
        $userApi->expects(self::once())->method('createUser')->willThrowException(new PasswordTooShort(15));

        $tester = $this->createTester($this->completeConfiguration(), $userApi);

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('Could not create initial administrator', $tester->getDisplay());
    }

    private function createTester(array $configuration, UserApi $userApi, ?File $file = null) : CommandTester
    {
        $command = new UserBootstrapAdmin(
            new Config($file ?? $this->createStub(File::class), $configuration),
            $userApi,
            $this->createStub(LoggerInterface::class),
        );
        (new Application())->add($command);

        return new CommandTester($command);
    }

    private function completeConfiguration() : array
    {
        return [
            'INITIAL_ADMIN_EMAIL' => 'admin@example.com',
            'INITIAL_ADMIN_USERNAME' => 'admin',
            'INITIAL_ADMIN_PASSWORD' => 'secure-password',
        ];
    }
}
