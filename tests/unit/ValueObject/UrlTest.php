<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\Exception\InvalidUrl;
use Movary\ValueObject\RelativeUrl;
use Movary\ValueObject\Url;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\ValueObject\Url */
class UrlTest extends TestCase
{
    public function testAppendRelativeUrl() : void
    {
        $relativeUrl = RelativeUrl::create('/relativeUrl?q=a');

        $subject = Url::createFromString('https://movary.org/path');

        self::assertEquals(
            Url::createFromString('https://movary.org/path/relativeUrl?q=a'),
            $subject->appendRelativeUrl($relativeUrl),
        );
    }

    public function testCreateThrowsExceptionIfUrlIsNotValid() : void
    {
        $this->expectException(InvalidUrl::class);
        $this->expectExceptionMessage('Not a valid url: not-valid');

        Url::createFromString('not-valid');
    }

    public function testGetPath() : void
    {
        $subject = Url::createFromString('https://movary.org/path');

        self::assertSame('/path', $subject->getPath());
    }
}
