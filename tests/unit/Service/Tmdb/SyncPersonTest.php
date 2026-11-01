<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service\Tmdb;

use Movary\Api\Tmdb\Dto\TmdbPerson;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Person\PersonApi;
use Movary\Domain\Person\PersonEntity;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\Tmdb\PersonChangesCalculator;
use Movary\Service\Tmdb\SyncPerson;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SyncPerson::class)]
class SyncPersonTest extends TestCase
{
    public function testFullSyncPassesExternalIdsIncludingClearedValuesToUpdate() : void
    {
        $tmdbPerson = TmdbPerson::createFromArray([
            'id' => 34,
            'imdb_id' => null,
            'name' => 'Person',
            'biography' => null,
            'birthday' => null,
            'deathday' => null,
            'gender' => 0,
            'popularity' => 1.0,
            'known_for_department' => null,
            'profile_path' => null,
            'place_of_birth' => null,
            'external_ids' => ['instagram_id' => 'example'],
        ]);
        $person = PersonEntity::createFromArray([
            'id' => 12,
            'tmdb_id' => 34,
            'name' => 'Person',
            'gender' => 0,
            'known_for_department' => null,
            'imdb_id' => null,
            'poster_path' => null,
            'tmdb_poster_path' => null,
            'biography' => null,
            'birth_date' => null,
            'death_date' => null,
            'place_of_birth' => null,
            'updated_at_tmdb' => null,
            'facebook_id' => 'removed',
        ]);
        $tmdbApi = $this->createMock(TmdbApi::class);
        $tmdbApi->expects(self::once())->method('fetchPersonDetails')->with(34)->willReturn($tmdbPerson);
        $personApi = $this->createMock(PersonApi::class);
        $personApi->expects(self::once())->method('findByTmdbId')->with(34)->willReturn($person);
        $personApi->expects(self::once())->method('update')->willReturnCallback(
            static function (mixed ...$arguments) use ($person) : PersonEntity {
                self::assertSame([
                    'facebook_id' => null,
                    'instagram_id' => 'example',
                    'tiktok_id' => null,
                    'twitter_id' => null,
                    'youtube_id' => null,
                    'wikidata_id' => null,
                ], $arguments[12]);

                return $person;
            },
        );

        $jobScheduler = $this->getMockBuilder(JobQueueScheduler::class)
            ->setConstructorArgs([$this->createStub(JobQueueApi::class), false])
            ->onlyMethods(['storePersonIdForTmdbImageCacheJob'])
            ->getMock();
        $jobScheduler->expects(self::never())->method('storePersonIdForTmdbImageCacheJob');

        $subject = new SyncPerson(
            $tmdbApi,
            $personApi,
            $jobScheduler,
            $this->createStub(LoggerInterface::class),
            $this->createStub(PersonChangesCalculator::class),
        );

        $subject->syncPerson(34);
    }
}
