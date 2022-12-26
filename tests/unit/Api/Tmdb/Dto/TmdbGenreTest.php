<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Dto;

use Movary\Api\Tmdb\Dto\TmdbGenre;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Api\Tmdb\Dto\TmdbGenre */
class TmdbGenreTest extends TestCase
{
    public function testCreateFromArray() : void
    {
        $subject = TmdbGenre::createFromArray(['id' => 12, 'name' => 'Horror']);

        self::assertSame(12, $subject->getId());
        self::assertSame('Horror', $subject->getName());
    }
}
