<?php declare(strict_types=1);

namespace Tests\Unit\Movary\JobQueue;

use InvalidArgumentException;
use Movary\JobQueue\TmdbImageCacheJobParameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TmdbImageCacheJobParameters::class)]
class TmdbImageCacheJobParametersTest extends TestCase
{
    public function testCreatesParameters() : void
    {
        $parameters = TmdbImageCacheJobParameters::create('persons', true);

        self::assertSame('persons', $parameters->getImageType());
        self::assertTrue($parameters->isForceRefresh());
    }

    public function testRejectsUnsupportedImageType() : void
    {
        $this->expectException(InvalidArgumentException::class);

        TmdbImageCacheJobParameters::create('posters', false);
    }
}
