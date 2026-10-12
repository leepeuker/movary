<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\Dto;

use Movary\HttpController\Api\Dto\MovieSearchResultDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieSearchResultDto::class)]
class MovieSearchResultDtoTest extends TestCase
{
    public function testSerializesOriginalTitle() : void
    {
        $result = MovieSearchResultDto::create(
            tmdbId: 129,
            title: 'Spirited Away',
            originalTitle: '千と千尋の神隠し',
        );

        self::assertSame('千と千尋の神隠し', $result->jsonSerialize()['originalTitle']);
    }
}
