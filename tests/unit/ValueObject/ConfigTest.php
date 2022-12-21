<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\Config;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\ValueObject\Config */
class ConfigTest extends TestCase
{
    private Config $subject;

    public function setUp() : void
    {
        $this->subject = Config::createFromEnv(
            [
                'string_test' => 'value',
                'int_test' => 2,
                'bool_test' => true,
            ],
        );
    }

    public function testGetAsBool() : void
    {
        self::assertSame(true, $this->subject->getAsBool('bool_test'));
        self::assertSame(false, $this->subject->getAsBool('bool_test_not_existing', false));

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Key does not exist: bool_test_not_existing');
        $this->subject->getAsBool('bool_test_not_existing');
    }

    public function testGetAsInt() : void
    {
        self::assertSame(2, $this->subject->getAsInt('int_test'));
        self::assertSame(3, $this->subject->getAsInt('int_test_not_existing', 3));

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Key does not exist: int_test_not_existing');
        $this->subject->getAsBool('int_test_not_existing');
    }

    public function testGetAsString() : void
    {
        self::assertSame('value', $this->subject->getAsString('string_test'));
        self::assertSame('fallback', $this->subject->getAsString('string_test_not_existing', 'fallback'));

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Key does not exist: string_test_not_existing');
        $this->subject->getAsBool('string_test_not_existing');
    }
}
