<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use Movary\Api\Jellyfin\Cache\JellyfinCache;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Tmdb\SyncMovie;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use RuntimeException;

class JellyfinMoviesImporter
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieApi $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly JellyfinCache $jellyfinCache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function executeJob(JobEntity $job) : void
    {
        $userId = $job->getUserId();
        if ($userId === null) {
            throw new RuntimeException('Missing userId');
        }

        $this->importMoviesFromJellyfin($userId);
    }

    public function importMoviesFromJellyfin(int $userId) : void
    {
        $watchedMoviesList = $this->jellyfinCache->fetchJellyfinPlayedMovies($userId);

        foreach ($watchedMoviesList as $watchedMovie) {
            $date = $watchedMovie->getLastWatchDate();
            if ($date === null) {
                continue;
            }

            $movie = $this->movieApi->findByTmdbId($watchedMovie->getTmdbId());

            if ($movie === null) {
                $movie = $this->tmdbMovieSyncService->syncMovie($watchedMovie->getTmdbId());
                $this->logger->debug(
                    'Jellyfin import: Missing movie created during import',
                    [
                        'movieId' => $movie->getId(),
                        'moveTitle' => $movie->getTitle(),
                        'tmdbId' => $movie->getTmdbId(),
                    ],
                );
            }

            $needsUpdate = true;
            foreach ($this->movieHistoryApi->fetchHistoryByMovieId($movie->getId(), $userId) as $watchDate) {
                if ($date->isEqual(Date::createFromString($watchDate['watched_at'])) === true) {
                    $needsUpdate = false;

                    break;
                }
            }

            if ($needsUpdate === false) {
                $this->logger->debug('Jellyfin import: Skipped movie watch date, no change', [
                    'movieId' => $movie->getId(),
                    'moveTitle' => $movie->getTitle(),
                    'tmdbId' => $movie->getTmdbId(),
                    'watchDate' => (string)$date,
                ]);

                continue;
            }

            $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $userId, $date);
            $this->logger->info('Jellyfin import: Movie watch date added', [
                'movieId' => $movie->getId(),
                'moveTitle' => $movie->getTitle(),
                'tmdbId' => $movie->getTmdbId(),
                'watchDate' => (string)$date,
            ]);
        }
    }
}
