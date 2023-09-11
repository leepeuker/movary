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
            $movieIds = $this->movieHistoryApi->fetchMovieIdsWithWatchDatesByUserId($userId);

            $forceExport = false;
        }

        $this->exportMoviesWatchStateToJellyfin($userId, $movieIds, $forceExport);
    }

    public function exportMoviesWatchStateToJellyfin(int $userId, array $movieIds, bool $removeWatchDates) : void
    {
        $watchedTmdbIds = $this->movieHistoryApi->fetchTmdbIdsWithWatchDatesByUserIdAndMovieIds($userId, $movieIds);

        $unwatchedTmdbIds = [];
        if ($removeWatchDates === true) {
            $unwatchedTmdbIds = $this->movieHistoryApi->fetchTmdbIdsWithoutWatchDateByUserId($userId, $movieIds);
        }

        $this->jellyfinApi->setMoviesWatchState($userId, $watchedTmdbIds, $unwatchedTmdbIds);
    }
}
