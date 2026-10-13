<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Tmdb;

use Movary\JobQueue\JobEntity;
use Movary\Service\Tmdb\SyncMovies;
use Movary\Util\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SyncMovies::class)]
class SyncMoviesTest extends TestCase
{
    #[DataProvider('provideJobParameters')]
    public function testExecuteJobPassesParametersToSync(?array $parameters, array $expectedParameters) : void
    {
        $subject = $this->getMockBuilder(SyncMovies::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['syncMovies'])
            ->getMock();
        $subject
            ->expects(self::once())
            ->method('syncMovies')
            ->with(
                $expectedParameters['maxAgeInHours'],
                $expectedParameters['threshold'],
                $expectedParameters['ids'],
                $expectedParameters['onlyNeverSynced'],
            );
        $job = JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'tmdb_movie_sync',
            'job_status' => 'waiting',
            'user_id' => null,
            'parameters' => $parameters === null ? null : Json::encode($parameters),
            'updated_at' => null,
            'created_at' => '2026-09-28 12:00:00',
        ]);
        $subject->executeJob($job);
    }

    public static function provideJobParameters() : array
    {
        return [
            'all options' => [[
                'movieIds' => [7, 8],
                'maxAgeInHours' => 48,
                'threshold' => 100,
                'onlyNeverSynced' => true,
            ], [
                'ids' => [7, 8],
                'maxAgeInHours' => 48,
                'threshold' => 100,
                'onlyNeverSynced' => true,
            ]],
            'legacy full-library sync' => [null, [
                'ids' => [],
                'maxAgeInHours' => null,
                'threshold' => null,
                'onlyNeverSynced' => false,
            ]],
        ];
    }
}
