<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Application\Api\Tmdb;

use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\ValueObject\Url;
use PHPUnit\Framework\TestCase;

class TmdbUrlGeneratorTest extends TestCase
{
    private TmdbUrlGenerator $subject;

    public function setUp() : void
    {
        $this->subject = new TmdbUrlGenerator();
    }

    public function testGenerateImageUrlWithDefaultSize() : void
    {
        self::assertEquals(
            Url::createFromString('https://image.tmdb.org/t/p/w342/foobar'),
            $this->subject->generateImageUrl('foobar')
        );
    }

    public function testGenerateImageUrlWithSize() : void
    {
        self::assertEquals(
            Url::createFromString('https://image.tmdb.org/t/p/size/foobar'),
            $this->subject->generateImageUrl('foobar', 'size')
        );
    }

    public function testGenerateMovieUrl() : void
    {
        self::assertEquals(
            Url::createFromString('https://www.themoviedb.org/movie/10/'),
            $this->subject->generateMovieUrl(10)
        );
    }
}
