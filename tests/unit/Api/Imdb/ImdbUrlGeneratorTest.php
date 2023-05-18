<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Imdb;

use Movary\Api\Imdb\ImdbUrlGenerator;
use PHPUnit\Framework\TestCase;

/** @covers \Movary\Api\Imdb\ImdbUrlGenerator */
class ImdbUrlGeneratorTest extends TestCase
{
    private ImdbUrlGenerator $subject;

    public function setUp() : void
    {
        $this->subject = new ImdbUrlGenerator();
    }

    public function testBuildMovieUrl() : void
    {
        $this->assertSame(
            'https://www.imdb.com/title/tt8760708',
            $this->subject->buildMovieUrl('tt8760708'),
        );
    }
}
