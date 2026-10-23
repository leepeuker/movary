<?php declare(strict_types=1);

namespace Tests\Unit\Movary\HttpController\Api\ResponseMapper;

use Movary\Domain\Movie\Review\MovieReviewEntity;
use Movary\HttpController\Api\ResponseMapper\MovieReviewResponseMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MovieReviewResponseMapper::class)]
class MovieReviewResponseMapperTest extends TestCase
{
    public function testMapsReview() : void
    {
        $review = MovieReviewEntity::createFromArray([
            'movie_id' => 12,
            'user_id' => 34,
            'review' => 'A review',
            'contains_spoilers' => 1,
            'created_at' => '2026-10-22 20:00:00',
            'updated_at' => '2026-10-23 08:00:00',
        ]);

        self::assertSame(
            [
                'review' => 'A review',
                'containsSpoilers' => true,
                'createdAt' => '2026-10-22 20:00:00',
                'updatedAt' => '2026-10-23 08:00:00',
            ],
            json_decode(
                json_encode((new MovieReviewResponseMapper())->mapReview($review), JSON_THROW_ON_ERROR),
                true,
                flags: JSON_THROW_ON_ERROR,
            ),
        );
    }
}
