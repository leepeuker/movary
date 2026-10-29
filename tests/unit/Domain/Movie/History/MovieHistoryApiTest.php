<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\History;

use Doctrine\DBAL\Connection;
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
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieHistoryApi::class)]
class MovieHistoryApiTest extends TestCase
{
    private MovieHistoryRepository&MockObject $repositoryMock;

    private MovieRepository&Stub $movieRepositoryStub;

    private MovieHistoryApi $subject;

    protected function setUp() : void
    {
        $this->repositoryMock = $this->createMock(MovieHistoryRepository::class);
        $dbConnection = $this->createStub(Connection::class);
        $dbConnection->method('transactional')->willReturnCallback(static fn(callable $operation) : mixed => $operation());
        $userApiMock = $this->createStub(UserApi::class);
        $userMock = $this->createStub(UserEntity::class);

        $userMock->method('hasJellyfinSyncEnabled')->willReturn(false);
        $userMock->method('isMastodonEnabled')->willReturn(false);
        $userApiMock->method('fetchUser')->willReturn($userMock);

        $this->movieRepositoryStub = $this->createStub(MovieRepository::class);

        $this->subject = new MovieHistoryApi(
            $this->repositoryMock,
            $this->movieRepositoryStub,
            $this->createStub(TmdbApi::class),
            $this->createStub(ImageUrlService::class),
            $this->createStub(JobQueueApi::class),
            $userApiMock,
            $dbConnection,
        );
    }

    public function testCreatePreservesExplicitPosition() : void
    {
        $watchedAt = Date::createFromString('2026-10-03');
        $created = [];

        $this->repositoryMock->expects(self::never())->method('fetchHighestPositionForWatchDate');
        $this->repositoryMock
            ->expects(self::exactly(2))
            ->method('createPlay')
            ->willReturnCallback(static function (int $movieId, int $userId, ?Date $date, ?string $comment, int $position, ?int $locationId) use (&$created) : int {
                $created[] = [$movieId, $userId, $date, $comment, $position, $locationId];

                return count($created);
            });

        $this->subject->create(11, 22, $watchedAt, 2, 3, 'Comment', 4);

        self::assertSame([
            [11, 22, $watchedAt, 'Comment', 3, 4],
            [11, 22, $watchedAt, null, 3, null],
        ], $created);
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
            ->method('createPlay')
            ->with(11, 22, $watchedAt, null, 4, null)
            ->willReturn(1);

        $this->subject->create(11, 22, $watchedAt, 1);
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
            ->method('createPlay')
            ->with(11, 22, $watchedAt, null, 1, null)
            ->willReturn(1);

        $this->subject->create(11, 22, $watchedAt, 1);
    }

    public function testUpdateReportsWhetherItCreatedPlays() : void
    {
        $this->repositoryMock->method('fetchPlaysByMovieAndDate')->willReturn([]);
        $this->repositoryMock->expects(self::once())->method('createPlay')->willReturn(42);

        self::assertTrue($this->subject->update(11, 22, null, 1, 1));
    }

    public function testUpdateWithoutNewPlaysReportsFalse() : void
    {
        $this->repositoryMock->method('fetchPlaysByMovieAndDate')->willReturn([[
            'id' => 42,
            'comment' => null,
            'location_id' => null,
        ]]);
        $this->repositoryMock->expects(self::never())->method('createPlay');

        self::assertFalse($this->subject->update(11, 22, null, 1, 1));
    }

    public function testFetchPersonalRatingDistributionIncludesRatingsWithoutEntries() : void
    {
        $this->repositoryMock->expects(self::never())->method('fetchHighestPositionForWatchDate');
        $this->movieRepositoryStub
            ->method('fetchPersonalRatingDistribution')
            ->willReturn([
                ['rating' => 2, 'count' => 3],
                ['rating' => 8, 'count' => 1],
            ]);

        self::assertSame(
            [
                ['rating' => 1, 'count' => 0],
                ['rating' => 2, 'count' => 3],
                ['rating' => 3, 'count' => 0],
                ['rating' => 4, 'count' => 0],
                ['rating' => 5, 'count' => 0],
                ['rating' => 6, 'count' => 0],
                ['rating' => 7, 'count' => 0],
                ['rating' => 8, 'count' => 1],
                ['rating' => 9, 'count' => 0],
                ['rating' => 10, 'count' => 0],
            ],
            $this->subject->fetchPersonalRatingDistribution(42),
        );
    }

    public function testFetchMostWatchedProductionCompaniesDoesNotFetchUnusedMovieTitles() : void
    {
        $productionCompanies = [
            ['id' => 7, 'name' => 'Studio', 'count' => 3, 'origin_country' => 'US'],
        ];
        $movieRepositoryMock = $this->createMock(MovieRepository::class);
        $movieRepositoryMock
            ->expects(self::once())
            ->method('fetchMostWatchedProductionCompanies')
            ->with(42, 12)
            ->willReturn($productionCompanies);
        $movieRepositoryMock->expects(self::never())->method('fetchMoviesByProductionCompany');
        $this->repositoryMock->expects(self::never())->method('fetchHighestPositionForWatchDate');
        $subject = new MovieHistoryApi(
            $this->repositoryMock,
            $movieRepositoryMock,
            $this->createStub(TmdbApi::class),
            $this->createStub(ImageUrlService::class),
            $this->createStub(JobQueueApi::class),
            $this->createStub(UserApi::class),
            $this->createStub(Connection::class),
        );

        self::assertSame(
            $productionCompanies,
            $subject->fetchMostWatchedProductionCompanies(42, 12),
        );
    }
}
