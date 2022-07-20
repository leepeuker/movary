<?php declare(strict_types=1);

namespace Movary\Worker;

use Movary\Application\Service\Tmdb\SyncMovies;
use Movary\Application\Service\Trakt;

class Service
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Trakt\SyncWatchedMovies $traktSyncWatchedMovies,
        private readonly Trakt\SyncRatings $traktSyncRatings,
        private readonly SyncMovies $tmdbSyncMovies
    ) {
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
            $job->isOfTypeTraktSyncRankings() => $this->traktSyncRatings->execute($parameters['userId']),
            $job->isOfTypeTraktSyncHistory() => $this->traktSyncWatchedMovies->execute($parameters['userId']),
            $job->isOfTypeTmdbSync() => $this->tmdbSyncMovies->syncMovies(),
            default => throw new \RuntimeException('Job type not supported: ' . $job->getType()),
        };
    }
}
