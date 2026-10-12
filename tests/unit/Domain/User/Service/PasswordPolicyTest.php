<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\User\Service;

use Movary\Domain\User\Exception\PasswordInvalidEncoding;
use Movary\Domain\User\Exception\PasswordTooShort;
use Movary\Domain\User\Service\PasswordPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordPolicy::class)]
class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $subject;

    protected function setUp() : void
    {
        $this->subject = new PasswordPolicy();
    }

    public function testRejectsFourteenAsciiCharacters() : void
    {
        $this->expectException(PasswordTooShort::class);

        $this->subject->ensurePasswordIsValid('12345678901234');
    }

    #[DataProvider('provideValidPasswords')]
    public function testAcceptsValidPassword(string $password) : void
    {
        $this->subject->ensurePasswordIsValid($password);

        self::assertSame($password, $password);
    }

    public function testReportsRequiredMinimumLength() : void
    {
        try {
            $this->subject->ensurePasswordIsValid('short');
        } catch (PasswordTooShort $exception) {
            self::assertSame(PasswordPolicy::MINIMUM_LENGTH, $exception->getMinLength());

            return;
        }

        self::fail('Expected a short password to be rejected.');
    }

    public function testRejectsInvalidUtf8() : void
    {
        $this->expectException(PasswordInvalidEncoding::class);

        $this->subject->ensurePasswordIsValid(str_repeat("\x80", PasswordPolicy::MINIMUM_LENGTH));
    }

    /** @return iterable<string, array{string}> */
    public static function provideValidPasswords() : iterable
    {
        yield 'fifteen ASCII characters' => ['123456789012345'];
        yield 'spaces are counted' => ['a phrase of 15+'];
        yield 'Unicode code points rather than bytes' => [str_repeat('ä', PasswordPolicy::MINIMUM_LENGTH)];
        yield '64 Unicode code points' => [str_repeat('ä', 64)];
        yield 'long passphrase' => [str_repeat('long passphrase ', 10)];
    }
}
