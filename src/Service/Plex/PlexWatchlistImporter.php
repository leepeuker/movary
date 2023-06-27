<?php declare(strict_types=1);

namespace Movary\Service\Plex;

use Movary\Api\Plex\PlexApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobEntity;
use Movary\Service\Tmdb\SyncMovie;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PlexWatchlistImporter
{
    public function __construct(
        private readonly PlexApi $plexApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSync,
        private readonly MovieApi $movieApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
        private readonly LoggerInterface $logger,
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
        $plexToken = (string)$this->userApi->fetchUser($userId)->getPlexToken();

        $plexWatchlistMovies = $this->plexApi->fetchWatchlist($plexToken);

        $timestamp = DateTime::create();

        foreach ($this->plexApi->findTmdbIdsOfWatchlistMovies($plexWatchlistMovies, $plexToken) as $tmdbId) {
            try {
                $this->importPlexWatchlistMovie($userId, (int)$tmdbId, $timestamp);

                $timestamp = $timestamp->subSeconds(1); // To prevent movies having the same timestamp which causes sorting issues
            } catch (\Exception $e) {
                $this->logger->warning(
                    'Could not import plex watchlist movie: ' . $e->getMessage(),
                    [
                        'tmdbId' => $tmdbId,
                        'exception' => $e
                    ],
                );

                continue;
            }
        }
    }

    private function importPlexWatchlistMovie(int $userId, int $tmdbId, DateTime $timestamp) : void
    {
        $movie = $this->movieApi->findByTmdbId($tmdbId);
        if ($movie === null) {
            $movie = $this->tmdbMovieSync->syncMovie($tmdbId);
        }

        $this->movieWatchlistApi->addMovieToWatchlist($userId, $movie->getId(), $timestamp);
    }
}
