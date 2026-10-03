<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\MovieHistoryRepository;
use Movary\Domain\Movie\MovieRepository;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\ImageUrlService;
use Movary\ValueObject\Date;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryApi::class)]
class MovieHistoryApiTest extends TestCase
{
    private MovieHistoryRepository&MockObject $repositoryMock;

    private MovieHistoryApi $subject;

    protected function setUp() : void
    {
        $this->repositoryMock = $this->createMock(MovieHistoryRepository::class);
        $userApiMock = $this->createStub(UserApi::class);
        $userMock = $this->createStub(UserEntity::class);

        $userMock->method('hasJellyfinSyncEnabled')->willReturn(false);
        $userMock->method('isMastodonEnabled')->willReturn(false);
        $userApiMock->method('fetchUser')->willReturn($userMock);

        $this->subject = new MovieHistoryApi(
            $this->repositoryMock,
            $this->createStub(MovieRepository::class),
            $this->createStub(TmdbApi::class),
            $this->createStub(ImageUrlService::class),
            $this->createStub(JobQueueApi::class),
            $userApiMock,
        );
    }

    public function testCreatePreservesExplicitPosition() : void
    {
        $watchedAt = Date::createFromString('2026-10-03');

        $this->repositoryMock->expects(self::never())->method('fetchHighestPositionForWatchDate');
        $this->repositoryMock
            ->expects(self::once())
            ->method('create')
            ->with(11, 22, $watchedAt, 2, 'Comment', 3, 4);

        $this->subject->create(11, 22, $watchedAt, 2, 3, 'Comment', 4);
    }

    public function testCreateIncrementsHighestPositionWhenPositionIsOmitted() : void
    {
        $watchedAt = Date::createFromString('2026-10-03');

        $this->repositoryMock
            ->expects(self::once())
            ->method('fetchHighestPositionForWatchDate')
            ->with(11, 22, $watchedAt)
            ->willReturn(3);
        $this->repositoryMock
            ->expects(self::once())
            ->method('create')
            ->with(11, 22, $watchedAt, 2, null, 4, null);

        $this->subject->create(11, 22, $watchedAt, 2);
    }

    public function testCreateUsesFirstPositionWhenNoPreviousPositionExists() : void
    {
        $watchedAt = Date::createFromString('2026-10-03');

        $this->repositoryMock
            ->expects(self::once())
            ->method('fetchHighestPositionForWatchDate')
            ->with(11, 22, $watchedAt)
            ->willReturn(null);
        $this->repositoryMock
            ->expects(self::once())
            ->method('create')
            ->with(11, 22, $watchedAt, 2, null, 1, null);

        $this->subject->create(11, 22, $watchedAt, 2);
    }
}
