<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\DateFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(\Movary\ValueObject\DateFormat::class)]
class DateFormatTest extends TestCase
{
    public function testGetFormats() : void
    {
        self::assertSame(
            [
                0 => [
                    'php' => 'y-m-d',
                    'javascript' => 'yy-mm-dd',
                ],
                1 => [
                    'php' => 'Y-m-d',
                    'javascript' => 'yyyy-mm-dd',
                ],
                2 => [
                    'php' => 'd.m.y',
                    'javascript' => 'dd.mm.yy',
                ],
                3 => [
                    'php' => 'd.m.Y',
                    'javascript' => 'dd.mm.yyyy',
                ],
            ],
            DateFormat::getFormats(),
        );
    }

    public function testGetJavascriptById() : void
    {
        self::assertSame('yy-mm-dd', DateFormat::getJavascriptById(0));
    }

    public function testGetJavascriptByIdThrowsExceptionIfOffsetDoesNotExist() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Id does not exist: 1000');

        self::assertSame('y-m-d', DateFormat::getJavascriptById(1000));
    }

    public function testGetJavascriptDefault() : void
    {
        self::assertSame('yy-mm-dd', DateFormat::getJavascriptDefault());
    }

    public function testGetPhpById() : void
    {
        self::assertSame('y-m-d', DateFormat::getPhpById(0));
    }

    public function testGetPhpByIdThrowsExceptionIfOffsetDoesNotExist() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Id does not exist: 1000');

        self::assertSame('y-m-d', DateFormat::getPhpById(1000));
    }

    public function testGetPhpDefault() : void
    {
        self::assertSame('y-m-d', DateFormat::getPhpDefault());
    }
}
