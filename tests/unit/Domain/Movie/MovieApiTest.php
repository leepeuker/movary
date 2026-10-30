<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use Movary\Api\Imdb\ImdbUrlGenerator;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Api\Tmdb\TmdbUrlGenerator;
use Movary\Domain\Movie\Cast\CastApi;
use Movary\Domain\Movie\Crew\CrewApi;
use Movary\Domain\Movie\Genre\MovieGenreApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\PlayCountConflict;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieRepository;
use Movary\Domain\Movie\ProductionCompany\ProductionCompanyApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\Person\PersonApi;
use Movary\Service\ImageUrlService;
use Movary\Service\VoteCountFormatter;
use Movary\ValueObject\Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieApi::class)]
class MovieApiTest extends TestCase
{
    public function testCreatingOnePlayRemovesMovieFromWatchlistAndReturnsId() : void
    {
        $date = Date::createFromString('2026-10-29');
        $historyApi = $this->createMock(MovieHistoryApi::class);
        $historyApi->expects(self::once())->method('createPlay')
            ->with(1, 2, $date, 3, 'Comment', null)
            ->willReturn(17);
        $watchlistApi = $this->createMock(MovieWatchlistApi::class);
        $watchlistApi->expects(self::once())->method('removeMovieFromWatchlistAutomatically')->with(1, 2);

        self::assertSame(17, $this->createSubject($historyApi, $watchlistApi)->createPlay(1, 2, $date, 3, 'Comment'));
    }

    public function testReplacingWithoutNewPlaysKeepsMovieOnWatchlist() : void
    {
        $historyApi = $this->createStub(MovieHistoryApi::class);
        $historyApi->method('update')->willReturn(false);
        $watchlistApi = $this->createMock(MovieWatchlistApi::class);
        $watchlistApi->expects(self::never())->method('removeMovieFromWatchlistAutomatically');

        $this->createSubject($historyApi, $watchlistApi)->replaceHistoryForMovieByDate(1, 2, null, 1);
    }

    public function testReplacingWithNewPlaysRemovesMovieFromWatchlist() : void
    {
        $historyApi = $this->createStub(MovieHistoryApi::class);
        $historyApi->method('update')->willReturn(true);
        $watchlistApi = $this->createMock(MovieWatchlistApi::class);
        $watchlistApi->expects(self::once())->method('removeMovieFromWatchlistAutomatically')->with(1, 2);

        $this->createSubject($historyApi, $watchlistApi)->replaceHistoryForMovieByDate(1, 2, null, 2);
    }

    public function testRejectedReductionKeepsMovieOnWatchlist() : void
    {
        $historyApi = $this->createStub(MovieHistoryApi::class);
        $historyApi->method('update')->willThrowException(new PlayCountConflict('Cannot reduce plays.'));
        $watchlistApi = $this->createMock(MovieWatchlistApi::class);
        $watchlistApi->expects(self::never())->method('removeMovieFromWatchlistAutomatically');

        $this->expectException(PlayCountConflict::class);
        $this->createSubject($historyApi, $watchlistApi)->replaceHistoryForMovieByDate(1, 2, null, 1);
    }

    public function testReplacementPassesOmittedMetadataThroughToTheSurvivingPlay() : void
    {
        $historyApi = $this->createMock(MovieHistoryApi::class);
        $historyApi->expects(self::once())->method('update')
            ->with(1, 2, null, 1, null, null, null, null, false)
            ->willReturn(false);
        $watchlistApi = $this->createStub(MovieWatchlistApi::class);

        $this->createSubject($historyApi, $watchlistApi)->replaceHistoryForMovieByDate(1, 2, null, 1);
    }

    private function createSubject(MovieHistoryApi $historyApi, MovieWatchlistApi $watchlistApi) : MovieApi
    {
        $movieRepository = $this->createStub(MovieRepository::class);

        return new MovieApi(
            $historyApi,
            $watchlistApi,
            $this->createStub(MovieGenreApi::class),
            $this->createStub(CastApi::class),
            $this->createStub(CrewApi::class),
            $this->createStub(TmdbApi::class),
            $movieRepository,
            $this->createStub(VoteCountFormatter::class),
            $this->createStub(ImdbUrlGenerator::class),
            $this->createStub(TmdbUrlGenerator::class),
            $this->createStub(ImageUrlService::class),
            $movieRepository,
            $this->createStub(ProductionCompanyApi::class),
            $this->createStub(PersonApi::class),
            $this->createStub(MovieHistoryLocationApi::class),
        );
    }
}
