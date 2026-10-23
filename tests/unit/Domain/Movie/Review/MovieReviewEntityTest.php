<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Domain\Movie\Review;

use Movary\Domain\Movie\Review\MovieReviewEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieReviewEntity::class)]
class MovieReviewEntityTest extends TestCase
{
    public function testCreateFromArrayMapsReviewData() : void
    {
        $review = MovieReviewEntity::createFromArray([
            'movie_id' => '12',
            'user_id' => '34',
            'review' => 'Thoughtful and beautifully shot.',
            'contains_spoilers' => '1',
            'created_at' => '2026-10-20 12:00:00',
            'updated_at' => '2026-10-21 13:30:00',
        ]);

        self::assertSame(12, $review->getMovieId());
        self::assertSame(34, $review->getUserId());
        self::assertSame('Thoughtful and beautifully shot.', $review->getReview());
        self::assertTrue($review->containsSpoilers());
        self::assertSame('2026-10-20 12:00:00', (string)$review->getCreatedAt());
        self::assertSame('2026-10-21 13:30:00', (string)$review->getUpdatedAt());
    }

    public function testCreateFromArrayMapsMissingUpdateTimeToNull() : void
    {
        $review = MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 34,
            'review' => 'A review.',
            'contains_spoilers' => 0,
            'created_at' => '2026-10-20 12:00:00',
            'updated_at' => null,
        ]);

        self::assertFalse($review->containsSpoilers());
        self::assertNull($review->getUpdatedAt());
    }
}
