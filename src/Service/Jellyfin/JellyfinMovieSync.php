<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\JobQueue\JobEntity;
use RuntimeException;

class JellyfinMovieSync
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly JellyfinApi $jellyfinApi,
    ) {
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $movieIds = $job->getParameters()['movieIds'] ?? [];

        $this->syncMoviesWatchStateToJellyfin($userId, $movieIds);
    }

    private function syncMoviesWatchStateToJellyfin(int $userId, array $movieIds) : void
    {
        $watchedTmdbIds = $this->movieHistoryApi->fetchTmdbIdsWithWatchHistoryByUserId($userId, $movieIds);
        $unwatchedTmdbIds = $this->movieHistoryApi->fetchTmdbIdsWithoutWatchHistoryByUserId($userId, $movieIds);

        $this->jellyfinApi->setMoviesWatchState($userId, $watchedTmdbIds, $unwatchedTmdbIds);
    }
}
