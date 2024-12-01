<?php declare(strict_types=1);

namespace Movary\Service;

use Movary\Api\Tmdb\Cache\TmdbImageCache;
use Movary\JobQueue\JobEntity;
use Movary\Service\Jellyfin\JellyfinMoviesExporter;
use Movary\Service\Jellyfin\JellyfinMoviesImporter;
use Movary\Service\Plex\PlexWatchlistImporter;
use Movary\Service\Tmdb\SyncMovies;
use Movary\Service\Trakt\ImportWatchedMovies;
use RuntimeException;

class JobProcessor
{
    public function __construct(
        private readonly ImportWatchedMovies $traktSyncWatchedMovies,
        private readonly Trakt\ImportRatings $traktSyncRatings,
        private readonly Letterboxd\LetterboxdImportRatings $letterboxdImportRatings,
        private readonly Letterboxd\LetterboxdImportDiary $letterboxdImportHistory,
        private readonly SyncMovies $tmdbSyncMovies,
        private readonly TmdbImageCache $tmdbImageCache,
        private readonly PlexWatchlistImporter $plexWatchlistImporter,
        private readonly JellyfinMoviesExporter $jellyfinExporter,
        private readonly JellyfinMoviesImporter $jellyfinImporter,
    ) {
    }

    public function processJob(JobEntity $job) : void
    {
        match (true) {
            $job->getType()->isOfTypeLetterboxdImportRankings() => $this->letterboxdImportRatings->executeJob($job),
            $job->getType()->isOfTypeLetterboxdImportHistory() => $this->letterboxdImportHistory->executeJob($job),
            $job->getType()->isOfTypeTmdbImageCache() => $this->tmdbImageCache->executeJob($job),
            $job->getType()->isOfTypeTraktImportRatings() => $this->traktSyncRatings->executeJob($job),
            $job->getType()->isOfTypeTraktImportHistory() => $this->traktSyncWatchedMovies->executeJob($job),
            $job->getType()->isOfTypeTmdbMovieSync() => $this->tmdbSyncMovies->syncMovies(),
            $job->getType()->isOfTypePlexImportWatchlist() => $this->plexWatchlistImporter->executeJob($job),
            $job->getType()->isOfTypeJellyfinExportMovies() => $this->jellyfinExporter->executeJob($job),
            $job->getType()->isOfTypeJellyfinImportMovies() => $this->jellyfinImporter->executeJob($job),

            default => throw new RuntimeException('Job type not supported: ' . $job->getType()),
        };
    }
}
