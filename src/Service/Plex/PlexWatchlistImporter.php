<?php declare(strict_types=1);

namespace Movary\Service\Plex;

use Movary\Api\Plex\PlexApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Tmdb\SyncMovie;
use RuntimeException;

class PlexWatchlistImporter
{
    public function __construct(
        private readonly PlexApi $plexApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSync,
        private readonly MovieApi $movieApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
    ) {
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->importPlexWatchlist($userId);
    }

    public function importPlexWatchlist(int $userId) : void
    {
        $plexToken = $this->userApi->fetchUser($userId)->getPlexToken();

        $plexWatchlistMovies = $this->plexApi->fetchWatchlist($plexToken);

        foreach ($this->plexApi->findTmdbIdsOfWatchlistMovies($plexWatchlistMovies, $plexToken) as $tmbdId) {
            $movie = $this->movieApi->findByTmdbId((int)$tmbdId);
            if ($movie === null) {
                $movie = $this->tmdbMovieSync->syncMovie((int)$tmbdId);
            }

            $this->movieWatchlistApi->addMovieToWatchlist($userId, $movie->getId());
            // TODO Log if movie was added to watchlist or not (it may already existed)
        }
    }
}
