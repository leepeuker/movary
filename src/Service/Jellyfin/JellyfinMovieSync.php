<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use RuntimeException;

class JellyfinMovieSync
{
    public function __construct(
        private readonly MovieApi $movieApi,
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

        $this->syncMovies($userId, $movieIds);
    }

    private function syncMovies(int $userId, array $movieIds) : void
    {
        $tmdbIdAndMovieIdList = $this->movieApi->fetchTmdbIdsByMovieIds($movieIds);

        $movieToTmdbIdMap = [];
        foreach ($tmdbIdAndMovieIdList as $tmdbIdAndMovieId) {
            $movieToTmdbIdMap[(int)$tmdbIdAndMovieId['id']] = $tmdbIdAndMovieId['tmdb_id'];
        }

        $watchedMovieIds = $this->movieHistoryApi->fetchMovieIdsWithUserWatchHistory($userId, $movieIds);

        foreach ($watchedMovieIds as $watchedMovieId) {
            $this->jellyfinApi->setMovieWatchState($userId, $movieToTmdbIdMap[(int)$watchedMovieId], true);
        }

        foreach (array_diff($movieIds, $watchedMovieIds) as $notWatchedMovieId) {
            $this->jellyfinApi->setMovieWatchState($userId, $movieToTmdbIdMap[$notWatchedMovieId], false);
        }
    }
}
