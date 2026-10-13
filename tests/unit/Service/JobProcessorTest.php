<?php declare(strict_types=1);

namespace Tests\Unit\Movary\Service;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Movary\JobQueue\JobEntity;
use Movary\Service\Email\PasswordResetEmailJobProcessor;
use Movary\Service\Jellyfin\JellyfinMoviesExporter;
use Movary\Service\Jellyfin\JellyfinMoviesImporter;
use Movary\Service\Imdb\ImdbMovieRatingSync;
use Movary\Service\JobProcessor;
use Movary\Service\Letterboxd\LetterboxdImportDiary;
use Movary\Service\Letterboxd\LetterboxdImportRatings;
use Movary\Service\Mastodon\MastodonPostPlayService;
use Movary\Service\Mastodon\MastodonPostWatchlistService;
use Movary\Service\Plex\PlexWatchlistImporter;
use Movary\Service\Tmdb\SyncMovies;
use Movary\Service\Tmdb\SyncPersons;
use Movary\Service\Trakt\ImportRatings as TraktImportRatings;
use Movary\Service\Trakt\ImportWatchedMovies;
use Movary\Util\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JobProcessor::class)]
class JobProcessorTest extends TestCase
{
    public function testProcessJobDispatchesTmdbMovieSyncJob() : void
    {
        $tmdbSyncMovies = $this->createMock(SyncMovies::class);
        $job = JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'tmdb_movie_sync',
            'job_status' => 'waiting',
            'user_id' => null,
            'parameters' => Json::encode(['movieIds' => [7, 8]]),
            'updated_at' => null,
            'created_at' => '2026-09-28 12:00:00',
        ]);
        $tmdbSyncMovies
            ->expects(self::once())
            ->method('executeJob')
            ->with($job);
        $subject = new JobProcessor(
            traktSyncWatchedMovies: $this->createStub(ImportWatchedMovies::class),
            traktSyncRatings: $this->createStub(TraktImportRatings::class),
            letterboxdImportRatings: $this->createStub(LetterboxdImportRatings::class),
            letterboxdImportHistory: $this->createStub(LetterboxdImportDiary::class),
            tmdbSyncMovies: $tmdbSyncMovies,
            tmdbSyncPersons: $this->createStub(SyncPersons::class),
            tmdbImageCache: $this->createStub(TmdbImageCache::class),
            imdbMovieRatingSync: $this->createStub(ImdbMovieRatingSync::class),
            plexWatchlistImporter: $this->createStub(PlexWatchlistImporter::class),
            jellyfinExporter: $this->createStub(JellyfinMoviesExporter::class),
            jellyfinImporter: $this->createStub(JellyfinMoviesImporter::class),
            mastodonPostPlayService: $this->createStub(MastodonPostPlayService::class),
            mastodonPostWatchlistService: $this->createStub(MastodonPostWatchlistService::class),
            passwordResetEmailJobProcessor: $this->createStub(PasswordResetEmailJobProcessor::class),
        );

        $subject->processJob($job);
    }

    public function testProcessJobDispatchesTmdbPersonSyncJob() : void
    {
        $tmdbSyncPersons = $this->createMock(SyncPersons::class);
        $job = JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'tmdb_person_sync',
            'job_status' => 'waiting',
            'user_id' => null,
            'parameters' => Json::encode(['personIds' => [7, 8]]),
            'updated_at' => null,
            'created_at' => '2026-09-28 12:00:00',
        ]);
        $tmdbSyncPersons
            ->expects(self::once())
            ->method('executeJob')
            ->with($job);
        $subject = new JobProcessor(
            traktSyncWatchedMovies: $this->createStub(ImportWatchedMovies::class),
            traktSyncRatings: $this->createStub(TraktImportRatings::class),
            letterboxdImportRatings: $this->createStub(LetterboxdImportRatings::class),
            letterboxdImportHistory: $this->createStub(LetterboxdImportDiary::class),
            tmdbSyncMovies: $this->createStub(SyncMovies::class),
            tmdbSyncPersons: $tmdbSyncPersons,
            tmdbImageCache: $this->createStub(TmdbImageCache::class),
            imdbMovieRatingSync: $this->createStub(ImdbMovieRatingSync::class),
            plexWatchlistImporter: $this->createStub(PlexWatchlistImporter::class),
            jellyfinExporter: $this->createStub(JellyfinMoviesExporter::class),
            jellyfinImporter: $this->createStub(JellyfinMoviesImporter::class),
            mastodonPostPlayService: $this->createStub(MastodonPostPlayService::class),
            mastodonPostWatchlistService: $this->createStub(MastodonPostWatchlistService::class),
            passwordResetEmailJobProcessor: $this->createStub(PasswordResetEmailJobProcessor::class),
        );

        $subject->processJob($job);
    }

    public function testProcessJobDispatchesPasswordResetEmailJob() : void
    {
        $passwordResetEmailJobProcessor = $this->createMock(PasswordResetEmailJobProcessor::class);
        $job = JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'password_reset_email',
            'job_status' => 'waiting',
            'user_id' => 12,
            'parameters' => Json::encode(['ignoreCooldown' => false]),
            'updated_at' => null,
            'created_at' => '2026-09-28 12:00:00',
        ]);
        $passwordResetEmailJobProcessor
            ->expects(self::once())
            ->method('executeJob')
            ->with($job);
        $subject = new JobProcessor(
            traktSyncWatchedMovies: $this->createStub(ImportWatchedMovies::class),
            traktSyncRatings: $this->createStub(TraktImportRatings::class),
            letterboxdImportRatings: $this->createStub(LetterboxdImportRatings::class),
            letterboxdImportHistory: $this->createStub(LetterboxdImportDiary::class),
            tmdbSyncMovies: $this->createStub(SyncMovies::class),
            tmdbSyncPersons: $this->createStub(SyncPersons::class),
            tmdbImageCache: $this->createStub(TmdbImageCache::class),
            imdbMovieRatingSync: $this->createStub(ImdbMovieRatingSync::class),
            plexWatchlistImporter: $this->createStub(PlexWatchlistImporter::class),
            jellyfinExporter: $this->createStub(JellyfinMoviesExporter::class),
            jellyfinImporter: $this->createStub(JellyfinMoviesImporter::class),
            mastodonPostPlayService: $this->createStub(MastodonPostPlayService::class),
            mastodonPostWatchlistService: $this->createStub(MastodonPostWatchlistService::class),
            passwordResetEmailJobProcessor: $passwordResetEmailJobProcessor,
        );

        $subject->processJob($job);
    }

    public function testProcessJobDispatchesImdbSyncJob() : void
    {
        $imdbMovieRatingSync = $this->createMock(ImdbMovieRatingSync::class);
        $job = JobEntity::createFromArray([
            'id' => 5,
            'job_type' => 'imdb_sync',
            'job_status' => 'waiting',
            'user_id' => 42,
            'parameters' => Json::encode(['movieIds' => [7, 8]]),
            'updated_at' => null,
            'created_at' => '2026-10-13 12:00:00',
        ]);
        $imdbMovieRatingSync->expects(self::once())->method('executeJob')->with($job);
        $subject = new JobProcessor(
            traktSyncWatchedMovies: $this->createStub(ImportWatchedMovies::class),
            traktSyncRatings: $this->createStub(TraktImportRatings::class),
            letterboxdImportRatings: $this->createStub(LetterboxdImportRatings::class),
            letterboxdImportHistory: $this->createStub(LetterboxdImportDiary::class),
            tmdbSyncMovies: $this->createStub(SyncMovies::class),
            tmdbSyncPersons: $this->createStub(SyncPersons::class),
            tmdbImageCache: $this->createStub(TmdbImageCache::class),
            imdbMovieRatingSync: $imdbMovieRatingSync,
            plexWatchlistImporter: $this->createStub(PlexWatchlistImporter::class),
            jellyfinExporter: $this->createStub(JellyfinMoviesExporter::class),
            jellyfinImporter: $this->createStub(JellyfinMoviesImporter::class),
            mastodonPostPlayService: $this->createStub(MastodonPostPlayService::class),
            mastodonPostWatchlistService: $this->createStub(MastodonPostWatchlistService::class),
            passwordResetEmailJobProcessor: $this->createStub(PasswordResetEmailJobProcessor::class),
        );

        $subject->processJob($job);
    }
}
