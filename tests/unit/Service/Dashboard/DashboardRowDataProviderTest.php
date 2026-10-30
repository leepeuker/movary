<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Dashboard;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Service\Dashboard\DashboardRowDataProvider;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\ValueObject\Gender;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(DashboardRowDataProvider::class)]
class DashboardRowDataProviderTest extends TestCase
{
    public function testActorRowUsesViewerFilterAndSixItemLimit() : void
    {
        $history = $this->createMock(MovieHistoryApi::class);
        $history->expects(self::once())->method('fetchActors')->with(
            42,
            6,
            1,
            null,
            'uniqueAppearances',
            null,
            Gender::createMale(),
            7,
        )->willReturn([]);
        $subject = new DashboardRowDataProvider($history, $this->createStub(MovieWatchlistApi::class));

        self::assertSame(['mostWatchedActors' => []], $subject->fetch(DashboardRow::createMostWatchedActors(), 42, 7));
    }

    public function testWatchlistUsesSixItemLimit() : void
    {
        $watchlist = $this->createMock(MovieWatchlistApi::class);
        $watchlist->expects(self::once())->method('fetchWatchlistPaginated')->with(42, 6, 1)->willReturn([]);
        $subject = new DashboardRowDataProvider($this->createStub(MovieHistoryApi::class), $watchlist);

        self::assertSame(['watchlistItems' => []], $subject->fetch(DashboardRow::createWatchlist(), 42, null));
    }

    public function testCompanyRowUsesTwelveItemLimit() : void
    {
        $history = $this->createMock(MovieHistoryApi::class);
        $history->expects(self::once())->method('fetchMostWatchedProductionCompanies')->with(42, 12)->willReturn([]);
        $subject = new DashboardRowDataProvider($history, $this->createStub(MovieWatchlistApi::class));

        self::assertSame(['mostWatchedProductionCompanies' => []], $subject->fetch(DashboardRow::createMostWatchedProductionCompanies(), 42, null));
    }

    public function testOtherRowsDispatchToTheirMatchingHistoryMethods() : void
    {
        $cases = [
            [DashboardRow::createLastPlays(), 'fetchLastPlays', 'lastPlays'],
            [DashboardRow::createLastPlaysCinema(), 'fetchLastPlaysCinema', 'lastPlaysCinema'],
            [DashboardRow::createMostWatchedActresses(), 'fetchActors', 'mostWatchedActresses'],
            [DashboardRow::createMostWatchedDirectors(), 'fetchDirectors', 'mostWatchedDirectors'],
            [DashboardRow::createMostWatchedGenres(), 'fetchMostWatchedGenres', 'mostWatchedGenres'],
            [DashboardRow::createMostWatchedLanguages(), 'fetchMostWatchedLanguages', 'mostWatchedLanguages'],
            [DashboardRow::createMostWatchedReleaseYears(), 'fetchMostWatchedReleaseYears', 'mostWatchedReleaseYears'],
            [DashboardRow::createMostWatchedProductionCountries(), 'fetchMostWatchedProductionCountries', 'mostWatchedProductionCountries'],
            [DashboardRow::createTopLocations(), 'fetchTopLocations', 'topLocations'],
            [DashboardRow::createPersonalRatingDistribution(), 'fetchPersonalRatingDistribution', 'personalRatingDistribution'],
        ];

        foreach ($cases as [$row, $method, $key]) {
            $history = $this->createMock(MovieHistoryApi::class);
            $history->expects(self::once())->method($method)->willReturn([]);
            $subject = new DashboardRowDataProvider($history, $this->createStub(MovieWatchlistApi::class));

            self::assertSame([$key => []], $subject->fetch($row, 42, 7));
        }
    }

    public function testUnsupportedRowFails() : void
    {
        $subject = new DashboardRowDataProvider($this->createStub(MovieHistoryApi::class), $this->createStub(MovieWatchlistApi::class));

        $this->expectException(RuntimeException::class);
        $subject->fetch(new DashboardRow(999, 'unknown', 'Unknown', true, false), 42, null);
    }
}
