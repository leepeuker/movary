<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Dto\WatchlistEntryDtoList;
use Movary\HttpController\Api\ResponseMapper\DashboardRowResponseMapper;
use Movary\HttpController\Api\ResponseMapper\WatchlistResponseMapper;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\Util\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DashboardRowResponseMapper::class)]
class DashboardRowResponseMapperTest extends TestCase
{
    public function testCountryAndLocationAliasesAreNormalized() : void
    {
        $subject = new DashboardRowResponseMapper($this->createStub(WatchlistResponseMapper::class));

        self::assertSame([
            'key' => 'most-watched-production-countries',
            'title' => 'Most watched Production Countries',
            'items' => [['name' => 'Germany', 'code' => 'DE', 'count' => 4]],
        ], $subject->map(DashboardRow::createMostWatchedProductionCountries(), [
            'mostWatchedProductionCountries' => [['id' => 'DE', 'name' => 'Germany', 'count_movies' => '4']],
        ])->jsonSerialize());

        self::assertSame([
            'key' => 'top-locations',
            'title' => 'Top Locations',
            'items' => [['id' => 2, 'name' => 'Cinema', 'count' => 3]],
        ], $subject->map(DashboardRow::createTopLocations(), [
            'topLocations' => [['id' => '2', 'name' => 'Cinema', 'count_plays' => '3']],
        ])->jsonSerialize());
    }

    public function testEmptyRowHasEmptyItems() : void
    {
        $subject = new DashboardRowResponseMapper($this->createStub(WatchlistResponseMapper::class));

        self::assertSame(['key' => 'most-watched-genres', 'title' => 'Most watched Genres', 'items' => []],
            $subject->map(DashboardRow::createMostWatchedGenres(), ['mostWatchedGenres' => []])->jsonSerialize());
    }

    public function testReusableStatisticShapes() : void
    {
        $subject = new DashboardRowResponseMapper($this->createStub(WatchlistResponseMapper::class));
        $cases = [
            [DashboardRow::createMostWatchedActors(), 'mostWatchedActors', ['id' => '9', 'name' => 'Person', 'poster_path' => '/person.jpg', 'uniqueCount' => '3'], ['id' => 9, 'name' => 'Person', 'profileUrl' => '/person.jpg', 'uniqueMovieCount' => 3]],
            [DashboardRow::createMostWatchedGenres(), 'mostWatchedGenres', ['name' => 'Drama', 'count' => '4'], ['name' => 'Drama', 'count' => 4]],
            [DashboardRow::createMostWatchedLanguages(), 'mostWatchedLanguages', ['name' => 'German', 'code' => 'de', 'count' => '5'], ['name' => 'German', 'count' => 5, 'code' => 'de']],
            [DashboardRow::createMostWatchedProductionCompanies(), 'mostWatchedProductionCompanies', ['id' => '8', 'name' => 'Studio', 'count' => '2'], ['name' => 'Studio', 'count' => 2, 'id' => 8]],
            [DashboardRow::createPersonalRatingDistribution(), 'personalRatingDistribution', ['rating' => '7', 'count' => '6'], ['rating' => 7, 'count' => 6]],
        ];

        foreach ($cases as [$row, $dataKey, $raw, $expected]) {
            $mapped = $subject->map($row, [$dataKey => [$raw]])->jsonSerialize();
            self::assertSame([$expected], $mapped['items']);
        }
    }

    public function testMoviePlayUsesCamelCaseWithoutExposingRepositoryFields() : void
    {
        $subject = new DashboardRowResponseMapper($this->createStub(WatchlistResponseMapper::class));
        $mapped = $subject->map(DashboardRow::createLastPlays(), ['lastPlays' => [[
            'id' => '12',
            'title' => 'Arrival',
            'original_title' => 'Arrival',
            'release_date' => '2016-11-10',
            'poster_path' => '/poster.jpg',
            'watched_at' => '2026-10-20',
            'user_rating' => '9',
        ]]])->jsonSerialize();

        self::assertSame(12, $mapped['items'][0]['movie']['id']);
        self::assertSame('/poster.jpg', $mapped['items'][0]['movie']['posterUrl']);
        self::assertSame(9, $mapped['items'][0]['personalRating']);
        self::assertArrayNotHasKey('watched_at', $mapped['items'][0]);
        self::assertArrayNotHasKey('original_title', $mapped['items'][0]['movie']);
    }

    public function testWatchlistUsesExistingEntryDtoShape() : void
    {
        $watchlistMapper = $this->createMock(WatchlistResponseMapper::class);
        $watchlistMapper->expects(self::once())->method('mapWatchlistEntries')->with([])->willReturn(WatchlistEntryDtoList::create());
        $subject = new DashboardRowResponseMapper($watchlistMapper);

        self::assertSame('{"key":"latest-watchlist","title":"Latest in Watchlist","items":[]}',
            Json::encode($subject->map(DashboardRow::createWatchlist(), ['watchlistItems' => []])));
    }
}
