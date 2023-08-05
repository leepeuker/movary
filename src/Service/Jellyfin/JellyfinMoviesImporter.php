<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Tmdb\SyncMovie;
use Psr\Log\LoggerInterface;
use RuntimeException;

class JellyfinMoviesImporter
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieApi $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly JellyfinApi $jellyfinApi,
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

    public function importMoviesFromJellyfin(int $userId)
    {
        $watchedMoviesList = $this->jellyfinApi->fetchWatchedMovies($userId);
        foreach($watchedMoviesList as $watchedMovie) {
            $movie = $this->movieApi->findByTmdbId($watchedMovie->getTmdbId());
            if($movie === null)  {
                $movie = $this->tmdbMovieSyncService->syncMovie($watchedMovie->getTmdbId());
                $this->logger->debug('Jellyfin: Missing movie created during import', ['movieId' => $movie->getId(), 'moveTitle' => $movie->getTitle()]);
            }
    
            $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $userId, $watchedMovie->getLastWatchDate());
            $this->logger->info('Jellyfin: Movie watch date imported', [
                'movieId' => $movie->getId(),
                'moveTitle' => $movie->getTitle(),
                'watchDate' => $watchedMovie->getLastWatchDate()
            ]);
        }
    }
}
