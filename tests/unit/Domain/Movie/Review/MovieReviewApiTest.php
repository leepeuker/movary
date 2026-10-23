<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\Review;

use InvalidArgumentException;
use Movary\Domain\Movie\Review\MovieReviewApi;
use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\Domain\Movie\Review\MovieReviewRepository;
use Movary\ValueObject\DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieReviewApi::class)]
class MovieReviewApiTest extends TestCase
{
    public function testCreateDelegatesToRepository() : void
    {
        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository
            ->expects(self::once())
            ->method('create')
            ->with(12, 34, "A review.\nSecond line.", true);

        (new MovieReviewApi($repository))->create(12, 34, "A review.\nSecond line.", true);
    }

    public function testCreateRejectsBlankReview() : void
    {
        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository->expects(self::never())->method('create');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A movie review must not be empty.');

        (new MovieReviewApi($repository))->create(12, 34, " \n\t", false);
    }

    public function testCreateRejectsReviewAboveUnicodeCharacterLimit() : void
    {
        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository->expects(self::never())->method('create');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A movie review must not exceed 10000 characters.');

        (new MovieReviewApi($repository))->create(12, 34, str_repeat('ä', 10_001), false);
    }

    public function testUpdateAcceptsReviewAtCharacterLimit() : void
    {
        $review = str_repeat('ä', MovieReviewApi::MAX_REVIEW_LENGTH);

        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository
            ->expects(self::once())
            ->method('update')
            ->with(12, 34, $review, false);

        (new MovieReviewApi($repository))->update(12, 34, $review, false);
    }

    public function testSaveCreatesMissingReview() : void
    {
        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository->expects(self::once())->method('find')->with(12, 34)->willReturn(null);
        $repository->expects(self::once())->method('create')->with(12, 34, 'A review.', true);
        $repository->expects(self::never())->method('update');

        (new MovieReviewApi($repository))->save(12, 34, 'A review.', true);
    }

    public function testSaveUpdatesExistingReview() : void
    {
        $review = MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 34,
            'review' => 'Old review.',
            'contains_spoilers' => 0,
            'created_at' => '2026-10-20 12:00:00',
            'updated_at' => null,
        ]);

        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository->expects(self::once())->method('find')->with(12, 34)->willReturn($review);
        $repository->expects(self::never())->method('create');
        $repository->expects(self::once())->method('update')->with(12, 34, 'Updated review.', false);

        (new MovieReviewApi($repository))->save(12, 34, 'Updated review.', false);
    }

    public function testImportUpdatesExistingReviewWithImportedTimestamps() : void
    {
        $review = MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 34,
            'review' => 'Old review.',
            'contains_spoilers' => 0,
            'created_at' => '2026-10-18 10:00:00',
            'updated_at' => null,
        ]);
        $createdAt = DateTime::createFromString('2026-10-20 12:00:00');

        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository->expects(self::once())->method('find')->with(12, 34)->willReturn($review);
        $repository->expects(self::never())->method('create');
        $repository
            ->expects(self::once())
            ->method('updateFromImport')
            ->with(12, 34, 'Imported review.', true, $createdAt, null);

        (new MovieReviewApi($repository))->import(12, 34, 'Imported review.', true, $createdAt, null);
    }

    public function testDeleteDelegatesToRepository() : void
    {
        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository->expects(self::once())->method('delete')->with(12, 34);

        (new MovieReviewApi($repository))->delete(12, 34);
    }

    public function testFindReturnsRepositoryResult() : void
    {
        $review = MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 34,
            'review' => 'A review.',
            'contains_spoilers' => 0,
            'created_at' => '2026-10-20 12:00:00',
            'updated_at' => null,
        ]);

        /** @var MovieReviewRepository&MockObject $repository */
        $repository = $this->createMock(MovieReviewRepository::class);
        $repository->expects(self::once())->method('find')->with(12, 34)->willReturn($review);

        self::assertSame($review, (new MovieReviewApi($repository))->find(12, 34));
    }
}
