<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Api\Tmdb\Dto;

use Movary\Api\Tmdb\Dto\TmdbCountryList;
use Movary\Api\Tmdb\Dto\TmdbGenreList;
use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\Dto\TmdbProductionCompanyList;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Movary\Api\Tmdb\Dto\TmdbMovie::class)]
class TmdbMovieTest extends TestCase
{
    public function testWithAllPossibleValuesEmptyOrNull() : void
    {
        $testData = [
            'id' => 12,
            'title' => 'title',
            'original_language' => 'originalLanguage',
            'tagline' => null,
            'overview' => null,
            'release_date' => '1998-08-28',
            'runtime' => null,
            'vote_average' => 5,
            'vote_count' => 1,
            'genres' => [],
            'production_companies' => [],
            'poster_path' => null,
            'backdrop_path' => null,
            'imdb_id' => null,
            'credits' => null,
            'production_countries' => [],
        ];

        $subject = TmdbMovie::createFromArray($testData);

        $this->assertGeneralGetters($subject);

        self::assertEquals(TmdbGenreList::createFromArray([]), $subject->getGenres());
        self::assertEquals(TmdbProductionCompanyList::createFromArray([]), $subject->getProductionCompanies());
        self::assertEquals(TmdbCountryList::createFromArray([]), $subject->getProductionCountries());

        self::assertNull($subject->getOverview());
        self::assertNull($subject->getRuntime());
        self::assertNull($subject->getTagline());
        self::assertNull($subject->getPosterPath());
        self::assertNull($subject->getBackdropPath());
        self::assertNull($subject->getImdbId());
    }

    public function testWithAllValuesSet() : void
    {
        $testData = [
            'id' => 12,
            'title' => 'title',
            'original_language' => 'originalLanguage',
            'tagline' => 'tagline...',
            'overview' => 'overview...',
            'release_date' => '1998-08-28',
            'runtime' => 90,
            'vote_average' => 5,
            'vote_count' => 1,
            'genres' => [
                [
                    'id' => 2,
                    'name' => 'Horror',
                ]
            ],
            'production_companies' => [
                [
                    'id' => 120,
                    'name' => 'company',
                    'origin_country' => 'US',
                ]
            ],
            'poster_path' => 'posterPath',
            'backdrop_path' => 'backdropPath',
            'imdb_id' => 'tt1234567',
            'production_countries' => [
                [
                    'iso_3166_1' => 'UK'
                ]
            ],
        ];

        $subject = TmdbMovie::createFromArray($testData);

        $this->assertGeneralGetters($subject);

        self::assertEquals(TmdbGenreList::createFromArray([['id' => 2, 'name' => 'Horror']]), $subject->getGenres());
        self::assertEquals(TmdbProductionCompanyList::createFromArray([['id' => 120, 'name' => 'company', 'origin_country' => 'US']]), $subject->getProductionCompanies());
        self::assertEquals(TmdbCountryList::createFromArray([['iso_3166_1' => 'UK']]), $subject->getProductionCountries());

        self::assertSame('overview...', $subject->getOverview());
        self::assertSame(90, $subject->getRuntime());
        self::assertSame('tagline...', $subject->getTagline());
        self::assertSame('posterPath', $subject->getPosterPath());
        self::assertSame('backdropPath', $subject->getBackdropPath());
        self::assertSame('tt1234567', $subject->getImdbId());
    }

    private function assertGeneralGetters(TmdbMovie $subject) : void
    {
        self::assertSame(12, $subject->getId());
        self::assertSame('title', $subject->getTitle());
        self::assertSame('originalLanguage', $subject->getOriginalLanguage());
        self::assertEquals(DateTime::createFromString('1998-08-28'), $subject->getReleaseDate());
        self::assertEquals(1, $subject->getVoteCount());
        self::assertEquals(5.0, $subject->getVoteAverage());
    }
}
