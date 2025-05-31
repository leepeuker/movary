<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Dto;

use Movary\Api\Tmdb\Dto\TmdbGenre;
use Movary\Api\Tmdb\Dto\TmdbGenreList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Api\Tmdb\Dto\TmdbGenreList::class)]
class TmdbGenreListTest extends TestCase
{
    public function testCreateFromArray() : void
    {
        $testData = [
            'id' => 120,
            'name' => 'company',
        ];

        $subject = TmdbGenreList::createFromArray([$testData]);

        self::assertCount(1, $subject);
        self::assertEquals(TmdbGenre::createFromArray($testData), $subject->asArray()[0]);
    }
}
