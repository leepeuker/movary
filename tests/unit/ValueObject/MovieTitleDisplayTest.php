<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use InvalidArgumentException;
use Movary\ValueObject\MovieTitleDisplay;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieTitleDisplay::class)]
class MovieTitleDisplayTest extends TestCase
{
    public function testRejectsUnknownOption() : void
    {
        $this->expectException(InvalidArgumentException::class);

        MovieTitleDisplay::createFromString('unknown');
    }
}
