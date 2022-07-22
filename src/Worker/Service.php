<?php declare(strict_types=1);

namespace Movary\Worker;

use Movary\Application\Service\Letterboxd;
use Movary\Application\Service\Tmdb\SyncMovies;
use Movary\Application\Service\Trakt;

class Service
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Trakt\SyncWatchedMovies $traktSyncWatchedMovies,
        private readonly Trakt\SyncRatings $traktSyncRatings,
        private readonly Letterboxd\ImportRatings $letterboxdImportRatings,
        private readonly Letterboxd\ImportHistory $letterboxdImportHistory,
        private readonly SyncMovies $tmdbSyncMovies,
    ) {
    }

    public function addLetterboxdImportHistoryJob(int $userId, string $importFile) : void
    {
        $job = Job::createLetterboxImportHistory($userId, $importFile);

        $this->repository->addJob($job);
    }

    public function addLetterboxdImportRatingsJob(int $userId, string $importFile) : void
    {
        $job = Job::createLetterboxImportRatings($userId, $importFile);

        $this->repository->addJob($job);
    }

    public function addTmdbSyncJob() : void
    {
        $job = Job::createTmdbSync();

        $this->repository->addJob($job);
    }

    public function addTraktHistorySyncJob(int $userId) : void
    {
        $job = Job::createTraktHistorySync($userId);

        $this->repository->addJob($job);
    }

    public function addTraktRatingsSyncJob(int $userId) : void
    {
        $job = Job::createTraktRatingsSync($userId);

        $this->repository->addJob($job);
    }

    public function processJob(Job $job) : void
    {
        $parameters = $job->getParameters();

        match (true) {
            $job->isOfTypeLetterboxdImportRankings() => $this->letterboxdImportRatings->execute($parameters['userId'], $parameters['importFile']),
            $job->isOfTypeLetterboxdImportHistory() => $this->letterboxdImportHistory->execute($parameters['userId'], $parameters['importFile']),
            $job->isOfTypeTraktSyncRankings() => $this->traktSyncRatings->execute($parameters['userId']),
            $job->isOfTypeTraktSyncHistory() => $this->traktSyncWatchedMovies->execute($parameters['userId']),
            $job->isOfTypeTmdbSync() => $this->tmdbSyncMovies->syncMovies(),
            default => throw new \RuntimeException('Job type not supported: ' . $job->getType()),
        };
    }
}
