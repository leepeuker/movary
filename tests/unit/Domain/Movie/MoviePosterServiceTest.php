<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbPoster;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\Movie\MoviePosterService;
use Movary\Domain\Movie\MovieRepository;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\ImageCacheService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoviePosterService::class)]
#[AllowMockObjectsWithoutExpectations]
class MoviePosterServiceTest extends TestCase
{
    private MockObject|TmdbApi $tmdbApi;
    private MockObject|MovieRepository $repository;
    private MockObject|ImageCacheService $imageCacheService;
    private MockObject|JobQueueScheduler $jobScheduler;
    private MoviePosterService $subject;

    protected function setUp() : void
    {
        $this->tmdbApi = $this->createMock(TmdbApi::class);
        $this->repository = $this->createMock(MovieRepository::class);
        $this->imageCacheService = $this->createMock(ImageCacheService::class);
        $this->jobScheduler = $this->getMockBuilder(JobQueueScheduler::class)
            ->setConstructorArgs([$this->createStub(JobQueueApi::class), false])
            ->onlyMethods(['storeMovieIdForTmdbImageCacheJob'])
            ->getMock();
        $this->subject = new MoviePosterService(
            $this->tmdbApi,
            $this->repository,
            $this->imageCacheService,
            $this->jobScheduler,
        );
    }

    public function testSelectValidatesAndInvalidatesCachedPoster() : void
    {
        $movie = $this->createMovie('/default.jpg', null, '/cached.jpg');
        $this->tmdbApi->expects(self::once())->method('fetchEnglishMoviePosters')->with(34)->willReturn([
            new TmdbPoster('/selected.jpg', 'https://example.com/selected.jpg'),
        ]);
        $this->repository->expects(self::once())->method('setTmdbPosterPathOverride')->with(12, '/selected.jpg');
        $this->imageCacheService->expects(self::once())->method('deleteImageByPosterPath')->with('/cached.jpg');
        $this->jobScheduler->expects(self::once())->method('storeMovieIdForTmdbImageCacheJob')->with(12);

        $this->subject->select($movie, '/selected.jpg');
    }

    public function testSelectRejectsPosterMissingFromEnglishResults() : void
    {
        $movie = $this->createMovie('/default.jpg', null, null);
        $this->tmdbApi->method('fetchEnglishMoviePosters')->willReturn([]);
        $this->repository->expects(self::never())->method('setTmdbPosterPathOverride');

        $this->expectException(InvalidArgumentException::class);
        $this->subject->select($movie, '/invalid.jpg');
    }

    public function testSelectingEffectivePosterIsIdempotent() : void
    {
        $movie = $this->createMovie('/default.jpg', '/selected.jpg', '/cached.jpg');
        $this->tmdbApi->method('fetchEnglishMoviePosters')->willReturn([
            new TmdbPoster('/selected.jpg', 'https://example.com/selected.jpg'),
        ]);
        $this->repository->expects(self::never())->method('setTmdbPosterPathOverride');
        $this->imageCacheService->expects(self::never())->method('deleteImageByPosterPath');
        $this->jobScheduler->expects(self::never())->method('storeMovieIdForTmdbImageCacheJob');

        $this->subject->select($movie, '/selected.jpg');
    }

    public function testRestoreDefaultClearsOverrideAndInvalidatesCache() : void
    {
        $movie = $this->createMovie('/default.jpg', '/selected.jpg', '/cached.jpg');
        $this->repository->expects(self::once())->method('clearTmdbPosterPathOverride')->with(12);
        $this->imageCacheService->expects(self::once())->method('deleteImageByPosterPath')->with('/cached.jpg');
        $this->jobScheduler->expects(self::once())->method('storeMovieIdForTmdbImageCacheJob')->with(12);

        $this->subject->restoreDefault($movie);
    }

    public function testRestoreDefaultWithoutOverrideIsIdempotent() : void
    {
        $movie = $this->createMovie('/default.jpg', null, '/cached.jpg');
        $this->repository->expects(self::never())->method('clearTmdbPosterPathOverride');
        $this->imageCacheService->expects(self::never())->method('deleteImageByPosterPath');
        $this->jobScheduler->expects(self::never())->method('storeMovieIdForTmdbImageCacheJob');

        $this->subject->restoreDefault($movie);
    }

    private function createMovie(?string $defaultPoster, ?string $overridePoster, ?string $cachedPoster) : MovieEntity
    {
        return MovieEntity::createFromArray([
            'id' => 12,
            'title' => 'Movie',
            'original_title' => null,
            'trakt_id' => null,
            'imdb_id' => null,
            'tmdb_id' => 34,
            'poster_path' => $cachedPoster,
            'overview' => null,
            'tagline' => null,
            'original_language' => 'en',
            'runtime' => 90,
            'release_date' => null,
            'tmdb_vote_average' => null,
            'tmdb_vote_count' => null,
            'tmdb_poster_path' => $defaultPoster,
            'tmdb_poster_path_override' => $overridePoster,
            'imdb_rating_average' => null,
            'imdb_rating_vote_count' => null,
            'updated_at_tmdb' => null,
            'updated_at_imdb' => null,
        ]);
    }
}
