<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Person;

use InvalidArgumentException;
use Movary\Api\Tmdb\Dto\TmdbProfile;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Person\PersonEntity;
use Movary\Domain\Person\PersonProfileService;
use Movary\Domain\Person\PersonRepository;
use Movary\JobQueue\JobQueueApi;
use Movary\JobQueue\JobQueueScheduler;
use Movary\Service\ImageCacheService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(PersonProfileService::class)]
#[AllowMockObjectsWithoutExpectations]
class PersonProfileServiceTest extends TestCase
{
    private MockObject|TmdbApi $tmdbApi;
    private MockObject|PersonRepository $repository;
    private MockObject|ImageCacheService $imageCacheService;
    private MockObject|JobQueueScheduler $jobScheduler;
    private PersonProfileService $subject;

    protected function setUp() : void
    {
        $this->tmdbApi = $this->createMock(TmdbApi::class);
        $this->repository = $this->createMock(PersonRepository::class);
        $this->imageCacheService = $this->createMock(ImageCacheService::class);
        $this->jobScheduler = $this->getMockBuilder(JobQueueScheduler::class)
            ->setConstructorArgs([$this->createStub(JobQueueApi::class), false])
            ->onlyMethods(['storePersonIdForTmdbImageCacheJob'])
            ->getMock();
        $this->subject = new PersonProfileService(
            $this->tmdbApi,
            $this->repository,
            $this->imageCacheService,
            $this->jobScheduler,
        );
    }

    public function testSelectValidatesAndInvalidatesCachedImage() : void
    {
        $person = $this->person(null, '/default.jpg', '/cached.jpg');
        $this->tmdbApi->expects(self::once())->method('fetchPersonProfiles')->with(34)->willReturn([
            new TmdbProfile('/selected.jpg', 'https://example.com/selected.jpg'),
        ]);
        $this->repository->expects(self::once())->method('setTmdbPosterPathOverride')->with(12, '/selected.jpg');
        $this->imageCacheService->expects(self::once())->method('deleteImageByPosterPath')->with('/cached.jpg');
        $this->jobScheduler->expects(self::once())->method('storePersonIdForTmdbImageCacheJob')->with(12);

        $this->subject->select($person, '/selected.jpg');
    }

    public function testSelectRejectsUnavailableProfile() : void
    {
        $this->tmdbApi->method('fetchPersonProfiles')->willReturn([]);
        $this->repository->expects(self::never())->method('setTmdbPosterPathOverride');

        $this->expectException(InvalidArgumentException::class);
        $this->subject->select($this->person(null, '/default.jpg', null), '/unavailable.jpg');
    }

    public function testSelectingCurrentProfileIsIdempotent() : void
    {
        $this->tmdbApi->method('fetchPersonProfiles')->willReturn([
            new TmdbProfile('/selected.jpg', 'https://example.com/selected.jpg'),
        ]);
        $this->repository->expects(self::never())->method('setTmdbPosterPathOverride');
        $this->imageCacheService->expects(self::never())->method('deleteImageByPosterPath');
        $this->jobScheduler->expects(self::never())->method('storePersonIdForTmdbImageCacheJob');

        $this->subject->select($this->person('/selected.jpg', '/default.jpg', '/cached.jpg'), '/selected.jpg');
    }

    public function testRestoreDefaultClearsOverrideAndInvalidatesCache() : void
    {
        $person = $this->person('/selected.jpg', '/default.jpg', '/cached.jpg');
        $this->repository->expects(self::once())->method('clearTmdbPosterPathOverride')->with(12);
        $this->imageCacheService->expects(self::once())->method('deleteImageByPosterPath')->with('/cached.jpg');
        $this->jobScheduler->expects(self::once())->method('storePersonIdForTmdbImageCacheJob')->with(12);

        $this->subject->restoreDefault($person);
    }

    public function testRestoreDefaultWithoutOverrideIsIdempotent() : void
    {
        $this->repository->expects(self::never())->method('clearTmdbPosterPathOverride');
        $this->jobScheduler->expects(self::never())->method('storePersonIdForTmdbImageCacheJob');

        $this->subject->restoreDefault($this->person(null, '/default.jpg', '/cached.jpg'));
    }

    private function person(?string $override, ?string $default, ?string $cached) : PersonEntity
    {
        return PersonEntity::createFromArray([
            'id' => 12,
            'name' => 'Person',
            'gender' => 0,
            'known_for_department' => null,
            'tmdb_id' => 34,
            'imdb_id' => null,
            'poster_path' => $cached,
            'tmdb_poster_path' => $default,
            'tmdb_poster_path_override' => $override,
            'biography' => null,
            'birth_date' => null,
            'death_date' => null,
            'place_of_birth' => null,
            'updated_at_tmdb' => null,
        ]);
    }
}
