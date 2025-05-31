<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\Util\File;
use Movary\ValueObject\Config;
use Movary\ValueObject\Exception\ConfigNotSetException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\Config::class)]
class ConfigTest extends TestCase
{
    private File|MockObject $fileUtilMock;

    private Config $subject;

    public function setUp() : void
    {
        $this->fileUtilMock = $this->createMock(File::class);

        $this->subject = new Config(
            $this->fileUtilMock,
            [
                'string_test' => 'value',
                'string_test_secret_FILE' => '/path/to/secret',
                'int_test' => 2,
                'bool_test' => true,
            ],
        );
    }

    public function testGetAsBool() : void
    {
        self::assertSame(true, $this->subject->getAsBool('bool_test'));
        self::assertSame(false, $this->subject->getAsBool('bool_test_not_existing', false));
    }

    public function testGetAsBoolThrowsExceptionWhenOnMissingValueAndFallback() : void
    {
        $this->fileUtilMock->expects(self::never())->method('fileExists');

        $this->expectException(ConfigNotSetException::class);
        $this->expectExceptionMessage('Required config not set: bool_test_not_existing');

        $this->subject->getAsBool('bool_test_not_existing');
    }

    public function testGetAsInt() : void
    {
        self::assertSame(2, $this->subject->getAsInt('int_test'));
        self::assertSame(3, $this->subject->getAsInt('int_test_not_existing', 3));
    }

    public function testGetAsIntThrowsExceptionWhenOnMissingValueAndFallback() : void
    {
        $this->fileUtilMock->expects(self::never())->method('fileExists');

        $this->expectException(ConfigNotSetException::class);
        $this->expectExceptionMessage('Required config not set: int_test_not_existing');

        $this->subject->getAsBool('int_test_not_existing');
    }

    public function testGetAsString() : void
    {
        self::assertSame('value', $this->subject->getAsString('string_test'));
        self::assertSame('fallback', $this->subject->getAsString('string_test_not_existing', 'fallback'));
    }

    public function testGetAsStringReturnsSecretAsFirstFallback() : void
    {
        $this->fileUtilMock
            ->expects(self::once())
            ->method('fileExists')
            ->with('/path/to/secret')
            ->willReturn(true);

        $this->fileUtilMock
            ->expects(self::once())
            ->method('readFile')
            ->with('/path/to/secret')
            ->willReturn('value_secret');

        self::assertSame('value_secret', $this->subject->getAsString('string_test_secret'));
    }

    public function testGetAsStringThrowsExceptionWhenOnMissingValueAndFallback() : void
    {
        $this->fileUtilMock->expects(self::never())->method('fileExists');

        $this->expectException(ConfigNotSetException::class);
        $this->expectExceptionMessage('Required config not set: string_test_not_existing');

        $this->subject->getAsString('string_test_not_existing');
    }

    public function testGetAsStringThrowsExceptionWhenOnMissingValueAndSecretFileAndFallback() : void
    {
        $this->fileUtilMock
            ->expects(self::once())
            ->method('fileExists')
            ->with('/path/to/secret')
            ->willReturn(false);

        $this->expectException(ConfigNotSetException::class);
        $this->expectExceptionMessage('Required config not set: string_test_secret');

        self::assertSame('value_secret', $this->subject->getAsString('string_test_secret'));
    }
}
