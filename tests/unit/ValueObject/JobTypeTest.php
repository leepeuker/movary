<?php declare(strict_types=1);

namespace Tests\Unit\Movary\ValueObject;

use Movary\ValueObject\JobType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(\Movary\ValueObject\JobType::class)]
class JobTypeTest extends TestCase
{
    public function testCreateImdbSync() : void
    {
        $subject = JobType::createImdbSync();

        self::assertSame('imdb_sync', (string)$subject);
    }

    public function testCreateLetterboxdImportHistory() : void
    {
        $subject = JobType::createLetterboxdImportHistory();

        self::assertSame('letterboxd_import_history', (string)$subject);
        self::assertTrue($subject->isOfTypeLetterboxdImportHistory());
    }

    public function testCreateLetterboxdImportRatings() : void
    {
        $subject = JobType::createLetterboxdImportRatings();

        self::assertSame('letterboxd_import_ratings', (string)$subject);
        self::assertTrue($subject->isOfTypeLetterboxdImportRankings());
    }

    public function testCreateThrowsExceptionIfJobTypeIsNotSupported() : void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not supported job type: foobar');

        JobType::createFromString('foobar');
    }

    public function testCreateTmdbImageCache() : void
    {
        $subject = JobType::createTmdbImageCache();

        self::assertSame('tmdb_image_cache', (string)$subject);
        self::assertTrue($subject->isOfTypeTmdbImageCache());
    }

    public function testCreateTmdbMovieSync() : void
    {
        $subject = JobType::createTmdbMovieSync();

        self::assertSame('tmdb_movie_sync', (string)$subject);
        self::assertTrue($subject->isOfTypeTmdbMovieSync());
    }

    public function testCreateTmdbPersonSync() : void
    {
        $subject = JobType::createTmdbPersonSync();

        self::assertSame('tmdb_person_sync', (string)$subject);
    }

    public function testCreateTraktImportHistory() : void
    {
        $subject = JobType::createTraktImportHistory();

        self::assertSame('trakt_import_history', (string)$subject);
        self::assertTrue($subject->isOfTypeTraktImportHistory());
    }

    public function testCreateTraktImportRatings() : void
    {
        $subject = JobType::createTraktImportRatings();

        self::assertSame('trakt_import_ratings', (string)$subject);
        self::assertTrue($subject->isOfTypeTraktImportRatings());
    }
}
