<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Movary\Service\Letterboxd;
use Movary\Service\Tmdb\SyncMovies;
use Movary\Service\Trakt;
use Movary\Service\Trakt\ImportWatchedMovies;
use Movary\ValueObject\Job;

class JobProcessor
{
    public function __construct(
        private readonly ImportWatchedMovies $traktSyncWatchedMovies,
        private readonly Trakt\ImportRatings $traktSyncRatings,
        private readonly Letterboxd\ImportRatings $letterboxdImportRatings,
        private readonly Letterboxd\ImportHistory $letterboxdImportHistory,
        private readonly SyncMovies $tmdbSyncMovies,
        private readonly TmdbImageCache $tmdbImageCache,
    ) {
    }

    public function processJob(Job $job) : void
    {
        match (true) {
            $job->getType()->isOfTypeLetterboxdImportRankings() => $this->letterboxdImportRatings->executeJob($job),
            $job->getType()->isOfTypeLetterboxdImportHistory() => $this->letterboxdImportHistory->executeJob($job),
            $job->getType()->isOfTypeTmdbImageCache() => $this->tmdbImageCache->executeJob($job),
            $job->getType()->isOfTypeTraktImportRatings() => $this->traktSyncRatings->executeJob($job),
            $job->getType()->isOfTypeTraktImportHistory() => $this->traktSyncWatchedMovies->executeJob($job),
            $job->getType()->isOfTypeTmdbSync() => $this->tmdbSyncMovies->syncMovies(),

            default => throw new \RuntimeException('Job type not supported: ' . $job->getType()),
        };
    }
}
