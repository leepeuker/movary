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

    public function testWatchedPersonMoviesAreUniqueByIdAndSortedCaseInsensitively() : void
    {
        $zebra = ['id' => 1, 'title' => 'zebra', 'userRating' => 8];
        $eclair = ['id' => 2, 'title' => 'Éclair', 'userRating' => null];
        $alpha = ['id' => 3, 'title' => 'Alpha', 'userRating' => 7];
        $anotherAlpha = ['id' => 4, 'title' => 'Alpha', 'userRating' => 6];
        $ecole = ['id' => 5, 'title' => 'école', 'userRating' => null];
        $repository = $this->createMock(MovieRepository::class);
        $repository->expects(self::once())->method('fetchWithActor')->with(12, 34)->willReturn([$zebra, $eclair, $alpha]);
        $repository->expects(self::once())->method('fetchWithDirector')->with(12, 34)->willReturn([$ecole, $anotherAlpha, $zebra]);
        $urlGenerator = $this->createMock(ImageUrlService::class);
        $urlGenerator->expects(self::exactly(2))->method('replacePosterPathWithImageSrcUrl')
            ->willReturnCallback(static fn(array $movies) : array => array_map(
                static fn(array $movie) : array => array_replace($movie, ['poster_path' => '/posters/' . $movie['id'] . '.jpg']),
                $movies,
            ));
        $subject = $this->createSubject($this->createStub(MovieHistoryApi::class), $this->createStub(MovieWatchlistApi::class), $repository, $urlGenerator);

        self::assertSame([
            array_replace($alpha, ['poster_path' => '/posters/3.jpg']),
            array_replace($anotherAlpha, ['poster_path' => '/posters/4.jpg']),
            array_replace($zebra, ['poster_path' => '/posters/1.jpg']),
            array_replace($eclair, ['poster_path' => '/posters/2.jpg']),
            array_replace($ecole, ['poster_path' => '/posters/5.jpg']),
        ], $subject->fetchWatchedWithPerson(12, 34));
    }

    public function testWatchedPersonMoviesCanBeEmpty() : void
    {
        $repository = $this->createMock(MovieRepository::class);
        $repository->expects(self::once())->method('fetchWithActor')->with(12, 34)->willReturn([]);
        $repository->expects(self::once())->method('fetchWithDirector')->with(12, 34)->willReturn([]);
        $urlGenerator = $this->createStub(ImageUrlService::class);
        $urlGenerator->method('replacePosterPathWithImageSrcUrl')->willReturnArgument(0);
        $subject = $this->createSubject($this->createStub(MovieHistoryApi::class), $this->createStub(MovieWatchlistApi::class), $repository, $urlGenerator);

        self::assertSame([], $subject->fetchWatchedWithPerson(12, 34));
    }

    private function createSubject(
        MovieHistoryApi $historyApi,
        MovieWatchlistApi $watchlistApi,
        ?MovieRepository $movieRepository = null,
        ?ImageUrlService $urlGenerator = null,
    ) : MovieApi {
        $movieRepository ??= $this->createStub(MovieRepository::class);

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
            $urlGenerator ?? $this->createStub(ImageUrlService::class),
            $movieRepository,
            $this->createStub(ProductionCompanyApi::class),
            $this->createStub(PersonApi::class),
            $this->createStub(MovieHistoryLocationApi::class),
        );
    }
}
