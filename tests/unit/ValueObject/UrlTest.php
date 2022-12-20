<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\Url;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\ValueObject\Url */
class UrlTest extends TestCase
{
    public function testCreateFromString() : void
    {
        $subject = Url::createFromString('https://movary.org');

        self::assertSame('https://movary.org', (string)$subject);
    }

    public function testCreateThrowsExceptionIfUrlIsNotValid() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid url: not-valid');

        Url::createFromString('not-valid');
    }

    public function testGetPath() : void
    {
        $subject = Url::createFromString('https://movary.org/path');

        self::assertSame('/path', $subject->getPath());
    }
}
