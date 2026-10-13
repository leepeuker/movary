<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Imdb;

use Movary\JobQueue\JobEntity;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\Util\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImdbMovieRatingSync::class)]
class ImdbMovieRatingSyncTest extends TestCase
{
    public function testExecuteJobMapsParameters() : void
    {
        $sync = $this->getMockBuilder(ImdbMovieRatingSync::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['syncMultipleMovieRatings'])
            ->getMock();
        $sync
            ->expects(self::once())
            ->method('syncMultipleMovieRatings')
            ->with(48, 100, [7, 8], true);

        $sync->executeJob(JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'imdb_sync',
            'job_status' => 'waiting',
            'user_id' => 42,
            'parameters' => Json::encode([
                'movieIds' => [7, 8],
                'maxAgeInHours' => 48,
                'threshold' => 100,
                'onlyNeverSynced' => true,
            ]),
            'updated_at' => null,
            'created_at' => '2026-10-13 12:00:00',
        ]));
    }
}
