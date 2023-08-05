<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\JobQueue\JobEntity;
use RuntimeException;

class JellyfinMoviesExporter
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
        $forceExport = true;

        if (count($movieIds) === 0) {
            $movieIds = $this->movieHistoryApi->fetchMovieIdsWithWatchHistoryByUserId($userId);

            $forceExport = false;
        }

        $this->exportMoviesWatchStateToJellyfin($userId, $movieIds, $forceExport);
    }

    private function exportMoviesWatchStateToJellyfin(int $userId, array $movieIds, bool $removeWatchDates) : void
    {
        $watchedTmdbIds = $this->movieHistoryApi->fetchTmdbIdsWithWatchHistoryByUserIdAndMovieIds($userId, $movieIds);

        $unwatchedTmdbIds = [];
        if ($removeWatchDates === true) {
            $unwatchedTmdbIds = $this->movieHistoryApi->fetchTmdbIdsWithoutWatchHistoryByUserId($userId, $movieIds);
        }

        $this->jellyfinApi->setMoviesWatchState($userId, $watchedTmdbIds, $unwatchedTmdbIds);
    }
}
