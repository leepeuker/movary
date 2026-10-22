<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\Review;

use Doctrine\DBAL\Connection;
use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\Domain\Movie\Review\MovieReviewRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieReviewRepository::class)]
#[CoversClass(MovieReviewEntity::class)]
class MovieReviewRepositoryTest extends TestCase
{
    public function testCreateStoresReview() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with(
                'movie_user_review',
                self::callback(
                    static fn(array $data) : bool => $data['movie_id'] === 12
                        && $data['user_id'] === 34
                        && $data['review'] === 'A review.'
                        && $data['contains_spoilers'] === 1
                        && is_string($data['created_at']),
                ),
            );

        (new MovieReviewRepository($connection))->create(12, 34, 'A review.', true);
    }

    public function testDeleteTargetsReviewOwnerAndMovie() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('delete')
            ->with('movie_user_review', ['movie_id' => 12, 'user_id' => 34]);

        (new MovieReviewRepository($connection))->delete(12, 34);
    }

    public function testDeleteAllForUserTargetsOnlyReviewRows() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('delete')
            ->with('movie_user_review', ['user_id' => 34]);

        (new MovieReviewRepository($connection))->deleteAllForUser(34);
    }

    public function testFetchVisibleForMovieAppliesPrivacyAndMapsJoinedData() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->with(
                self::callback(
                    static fn(string $query) : bool => str_contains($query, 'author.privacy_level = 2')
                        && str_contains($query, 'author.privacy_level >= 1')
                        && str_contains($query, 'author.id = ?')
                        && str_contains($query, 'LEFT JOIN `movie_user_rating`'),
                ),
                [12, 99, 99],
            )
            ->willReturn([[
                'movie_id' => '12',
                'user_id' => '34',
                'username' => 'alice',
                'review' => 'A review.',
                'contains_spoilers' => '1',
                'personal_rating' => '8',
                'created_at' => '2026-10-20 12:00:00',
                'updated_at' => null,
            ]]);

        $reviews = (new MovieReviewRepository($connection))->fetchVisibleForMovie(12, 99);

        self::assertSame(12, $reviews[0]['movieId']);
        self::assertSame(34, $reviews[0]['userId']);
        self::assertSame('alice', $reviews[0]['username']);
        self::assertSame('A review.', $reviews[0]['review']);
        self::assertTrue($reviews[0]['containsSpoilers']);
        self::assertSame(8, $reviews[0]['personalRating']);
        self::assertSame('2026-10-20 12:00:00', (string)$reviews[0]['createdAt']);
        self::assertNull($reviews[0]['updatedAt']);
    }

    public function testFindReturnsReview() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchAssociative')
            ->with('SELECT * FROM `movie_user_review` WHERE movie_id = ? AND user_id = ?', [12, 34])
            ->willReturn([
                'movie_id' => 12,
                'user_id' => 34,
                'review' => 'A review.',
                'contains_spoilers' => 0,
                'created_at' => '2026-10-20 12:00:00',
                'updated_at' => null,
            ]);

        $review = (new MovieReviewRepository($connection))->find(12, 34);

        self::assertInstanceOf(MovieReviewEntity::class, $review);
        self::assertSame('A review.', $review->getReview());
    }

    public function testFindReturnsNullWhenReviewDoesNotExist() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchAssociative')->willReturn(false);

        self::assertNull((new MovieReviewRepository($connection))->find(12, 34));
    }

    public function testUpdatePreservesCreationTimeAndChangesUpdateTime() : void
    {
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'movie_user_review',
                self::callback(
                    static fn(array $data) : bool => $data['review'] === 'Updated review.'
                        && $data['contains_spoilers'] === 0
                        && is_string($data['updated_at'])
                        && array_key_exists('created_at', $data) === false,
                ),
                ['movie_id' => 12, 'user_id' => 34],
            );

        (new MovieReviewRepository($connection))->update(12, 34, 'Updated review.', false);
    }
}
