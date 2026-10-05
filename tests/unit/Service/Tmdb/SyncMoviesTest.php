<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Tmdb;

use ArrayIterator;
use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Service\Tmdb\SyncMovies;
use Movary\Util\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SyncMovies::class)]
class SyncMoviesTest extends TestCase
{
    #[DataProvider('provideJobParameters')]
    public function testExecuteJobPassesMovieIdsToSync(?array $parameters, array $expectedMovieIds) : void
    {
        $movieApi = $this->createMock(MovieApi::class);
        $movieApi
            ->expects(self::once())
            ->method('fetchAllOrderedByLastUpdatedAtTmdbAsc')
            ->with(null, $expectedMovieIds)
            ->willReturn(new ArrayIterator());
        $job = JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'tmdb_movie_sync',
            'job_status' => 'waiting',
            'user_id' => null,
            'parameters' => $parameters === null ? null : Json::encode($parameters),
            'updated_at' => null,
            'created_at' => '2026-09-28 12:00:00',
        ]);
        $subject = new SyncMovies(
            $this->createStub(SyncMovie::class),
            $movieApi,
            $this->createStub(LoggerInterface::class),
        );

        $subject->executeJob($job);
    }

    public static function provideJobParameters() : array
    {
        return [
            'targeted sync' => [['movieIds' => [7, 8]], [7, 8]],
            'legacy full-library sync' => [null, []],
        ];
    }
}
