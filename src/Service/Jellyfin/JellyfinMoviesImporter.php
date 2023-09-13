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
        $jellyfinPlayedMovies = $this->jellyfinCache->fetchJellyfinPlayedMovies($userId);
        $tmdbIdToExistingMovieMap = $this->movieApi->findByTmdbIds($jellyfinPlayedMovies->getTmdbIds());

        foreach ($jellyfinPlayedMovies as $jellyfinPlayedMovie) {
            $jellyfinLastWatchDate = $jellyfinPlayedMovie->getLastWatchDate();
            if ($jellyfinLastWatchDate === null) {
                continue;
            }

            $movie = $tmdbIdToExistingMovieMap[$jellyfinPlayedMovie->getTmdbId()] ?? null;

            if ($movie === null) {
                $movie = $this->tmdbMovieSyncService->syncMovie($jellyfinPlayedMovie->getTmdbId());
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
                $movaryLastWatchDate = Date::createFromString($watchDate['watched_at']);

                if ($jellyfinLastWatchDate->isEqual($movaryLastWatchDate) === true) {
                    $needsUpdate = false;

                    break;
                }
            }

            if ($needsUpdate === false) {
                $this->logger->debug('Jellyfin import: Skipped movie watch date, no change', [
                    'movieId' => $movie->getId(),
                    'moveTitle' => $movie->getTitle(),
                    'tmdbId' => $movie->getTmdbId(),
                    'watchDate' => (string)$jellyfinLastWatchDate,
                ]);

                continue;
            }

            $this->movieApi->addPlaysForMovieOnDate($movie->getId(), $userId, $jellyfinLastWatchDate);
            $this->logger->info('Jellyfin import: Movie watch date added', [
                'movieId' => $movie->getId(),
                'moveTitle' => $movie->getTitle(),
                'tmdbId' => $movie->getTmdbId(),
                'watchDate' => (string)$jellyfinLastWatchDate,
            ]);
        }
    }
}
