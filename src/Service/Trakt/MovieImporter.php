<?php declare(strict_types=1);

namespace Movary\Service\Trakt;

use Movary\Api\Trakt\ValueObject\TraktMovie;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Service\Tmdb\SyncMovie;
use Psr\Log\LoggerInterface;

class MovieImporter
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
        private readonly SyncMovie $tmdbMovieSync,
    ) {
    }

    public function importMovie(TraktMovie $traktMovie) : MovieEntity
    {
        $traktId = $traktMovie->getTraktId();
        $tmdbId = $traktMovie->getTmdbId();

        $movie = $this->movieApi->findByTraktId($traktId);

        if ($movie !== null) {
            return $movie;
        }

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie !== null) {
            $this->movieApi->updateTraktId($movie->getId(), $traktId);

            return $this->movieApi->fetchByTraktId($traktId);
        }

        $movie = $this->tmdbMovieSync->syncMovie($tmdbId);
        $this->movieApi->updateTraktId($movie->getId(), $traktId);

        $this->logger->info('Trakt history import: Added new movie: ' . $movie->getTitle());

        return $this->movieApi->fetchByTraktId($traktId);
    }
}
