<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\Util\Json;
use Movary\ValueObject\Exception\InvalidRelativeUrl;
use Movary\ValueObject\RelativeUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\ValueObject\RelativeUrl::class)]
class RelativeUrlTest extends TestCase
{
    public function testCreateThrowsExceptionIfUrlIsNotValid() : void
    {
        $this->expectException(InvalidRelativeUrl::class);
        $this->expectExceptionMessage('Not a valid relative url: not-valid');

        RelativeUrl::create('not-valid');
    }

    public function testJsonSerialize() : void
    {
        $subject = RelativeUrl::create('/relativeUrl?q=a');

        self::assertSame('"\/relativeUrl?q=a"', Json::encode($subject));
    }

    public function testToString() : void
    {
        $subject = RelativeUrl::create('/relativeUrl?q=a');

        self::assertSame('/relativeUrl?q=a', (string)$subject);
    }
}
