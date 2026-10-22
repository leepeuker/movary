<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Tmdb;

use Doctrine\DBAL\Connection;
use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Company\CompanyEntityList;
use Movary\Domain\Genre\GenreEntityList;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\Tmdb\GenreConverter;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Service\Tmdb\SyncProductionCompany;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(SyncMovie::class)]
#[AllowMockObjectsWithoutExpectations]
class SyncMovieTest extends TestCase
{
    public function testChangedTmdbDefaultPreservesOverrideAndDoesNotSchedulePosterRecache() : void
    {
        $tmdbMovie = TmdbMovie::createFromArray([
            'id' => 34,
            'title' => 'Movie',
            'original_title' => 'Movie',
            'original_language' => 'en',
            'tagline' => null,
            'overview' => null,
            'release_date' => '',
            'runtime' => 90,
            'vote_average' => 7.0,
            'vote_count' => 10,
            'genres' => [],
            'production_companies' => [],
            'production_countries' => [],
            'poster_path' => '/new-default.jpg',
            'backdrop_path' => null,
            'imdb_id' => null,
            'credits' => null,
        ]);
        $beforeSync = $this->createMovie('/old-default.jpg', '/override.jpg');
        $afterSync = $this->createMovie('/new-default.jpg', '/override.jpg');

        $tmdbApi = $this->createMock(TmdbApi::class);
        $tmdbApi->expects(self::once())->method('fetchMovieDetails')->with(34)->willReturn($tmdbMovie);
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi->expects(self::once())->method('findByTmdbId')->with(34)->willReturn($beforeSync);
        $movieApi
            ->expects(self::once())
            ->method('updateDetails')
            ->with(
                movieId: 12,
                tagline: null,
                overview: null,
                originalLanguage: 'en',
                releaseDate: null,
                runtime: 90,
                tmdbVoteAverage: 7.0,
                tmdbVoteCount: 10,
                tmdbPosterPath: '/new-default.jpg',
                tmdbBackdropPath: null,
                imdbId: null,
                originalTitle: 'Movie',
            )
            ->willReturn($afterSync);

        $genreConverter = $this->createMock(GenreConverter::class);
        $genreConverter->method('getMovaryGenresFromTmdbMovie')->willReturn(GenreEntityList::create());
        $productionCompanyConverter = $this->createMock(SyncProductionCompany::class);
        $productionCompanyConverter
            ->method('syncMovaryProductionCompaniesFromTmdbMovie')
            ->willReturn(CompanyEntityList::create());
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('commit');
        $jobScheduler = $this->getMockBuilder(JobQueueScheduler::class)
            ->setConstructorArgs([$this->createStub(JobQueueApi::class), false])
            ->onlyMethods(['storeMovieIdForTmdbImageCacheJob'])
            ->getMock();
        $jobScheduler->expects(self::never())->method('storeMovieIdForTmdbImageCacheJob');

        $subject = new SyncMovie(
            $tmdbApi,
            $movieApi,
            $genreConverter,
            $productionCompanyConverter,
            $connection,
            $jobScheduler,
            new NullLogger(),
        );

        $result = $subject->syncMovie(34);

        self::assertSame('/override.jpg', $result->getTmdbPosterPathOverride());
        self::assertSame('/override.jpg', $result->getTmdbPosterPathEffective());
    }

    private function createMovie(string $defaultPoster, string $overridePoster) : MovieEntity
    {
        return MovieEntity::createFromArray([
            'id' => 12,
            'title' => 'Movie',
            'original_title' => 'Movie',
            'trakt_id' => null,
            'imdb_id' => null,
            'tmdb_id' => 34,
            'poster_path' => '/cached.jpg',
            'overview' => null,
            'tagline' => null,
            'original_language' => 'en',
            'runtime' => 90,
            'release_date' => null,
            'tmdb_vote_average' => 7.0,
            'tmdb_vote_count' => 10,
            'tmdb_poster_path' => $defaultPoster,
            'tmdb_poster_path_override' => $overridePoster,
            'imdb_rating_average' => null,
            'imdb_rating_vote_count' => null,
            'updated_at_tmdb' => null,
            'updated_at_imdb' => null,
        ]);
    }
}
