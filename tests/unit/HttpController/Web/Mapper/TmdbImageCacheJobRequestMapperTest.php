<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Web\Mapper;

use InvalidArgumentException;
use Movary\HttpController\Web\Mapper\TmdbImageCacheJobRequestMapper;
use Movary\ValueObject\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TmdbImageCacheJobRequestMapper::class)]
class TmdbImageCacheJobRequestMapperTest extends TestCase
{
    public function testMapsParameters() : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn([
            'imageType' => 'movies',
            'forceRefresh' => '1',
            'movieIds' => '7, 8,7',
            'personIds' => '9',
        ]);

        $parameters = (new TmdbImageCacheJobRequestMapper())->map($request);

        self::assertSame('movies', $parameters->getImageType());
        self::assertTrue($parameters->isForceRefresh());
        self::assertSame([7, 8], $parameters->getMovieIds());
        self::assertSame([9], $parameters->getPersonIds());
        self::assertFalse($parameters->isFullRefresh());
    }

    #[DataProvider('provideInvalidParameters')]
    public function testRejectsInvalidParameters(array $parameters) : void
    {
        $request = $this->createStub(Request::class);
        $request->method('getPostParameters')->willReturn($parameters);
        $this->expectException(InvalidArgumentException::class);

        (new TmdbImageCacheJobRequestMapper())->map($request);
    }

    public static function provideInvalidParameters() : iterable
    {
        yield 'missing image type' => [[]];
        yield 'unknown image type' => [['imageType' => 'posters']];
        yield 'invalid force refresh' => [['imageType' => 'all', 'forceRefresh' => 'yes']];
        yield 'invalid movie IDs' => [['imageType' => 'all', 'movieIds' => '7,nope']];
        yield 'invalid person IDs' => [['imageType' => 'all', 'personIds' => '0']];
    }
}
