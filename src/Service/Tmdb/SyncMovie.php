<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Doctrine\DBAL\Connection;
use Exception;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\JobQueue\JobQueueScheduler;
use Psr\Log\LoggerInterface;
use Throwable;

class SyncMovie
{
    private const int SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS = 1000000;

    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly MovieApi $movieApi,
        private readonly GenreConverter $genreConverter,
        private readonly ProductionCompanyConverter $productionCompanyConverter,
        private readonly Connection $dbConnection,
        private readonly JobQueueScheduler $jobScheduler,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncMovie(int $tmdbId) : MovieEntity
    {
        try {
            $tmdbMovie = $this->tmdbApi->fetchMovieDetails($tmdbId);
        } catch (Throwable) {
            /** @psalm-suppress ArgumentTypeCoercion */
            usleep(self::SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS);

            $tmdbMovie = $this->tmdbApi->fetchMovieDetails($tmdbId);
        }

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        $this->dbConnection->beginTransaction();

        $createdMovie = false;

        try {
            if ($movie === null) {
                $movie = $this->movieApi->create(
                    title: $tmdbMovie->getTitle(),
                    tmdbId: $tmdbId,
                    tagline: $tmdbMovie->getTagline(),
                    overview: $tmdbMovie->getOverview(),
                    originalLanguage: $tmdbMovie->getOriginalLanguage(),
                    releaseDate: $tmdbMovie->getReleaseDate()?->asDate(),
                    runtime: $tmdbMovie->getRuntime(),
                    tmdbVoteAverage: $tmdbMovie->getVoteAverage(),
                    tmdbVoteCount: $tmdbMovie->getVoteCount(),
                    tmdbPosterPath: $tmdbMovie->getPosterPath(),
                    tmdbBackdropPath: $tmdbMovie->getBackdropPath(),
                    imdbId: $tmdbMovie->getImdbId(),
                );

                $this->jobScheduler->storeMovieIdForTmdbImageCacheJob($movie->getId());

                $createdMovie = true;
            } else {
                $originalTmdbPosterPath = $movie->getTmdbPosterPath();

                $movie = $this->movieApi->updateDetails(
                    movieId: $movie->getId(),
                    tagline: $tmdbMovie->getTagline(),
                    overview: $tmdbMovie->getOverview(),
                    originalLanguage: $tmdbMovie->getOriginalLanguage(),
                    releaseDate: $tmdbMovie->getReleaseDate(),
                    runtime: $tmdbMovie->getRuntime(),
                    tmdbVoteAverage: $tmdbMovie->getVoteAverage(),
                    tmdbVoteCount: $tmdbMovie->getVoteCount(),
                    tmdbPosterPath: $tmdbMovie->getPosterPath(),
                    tmdbBackdropPath: $tmdbMovie->getBackdropPath(),
                    imdbId: $movie->getImdbId(),
                );

                if ($originalTmdbPosterPath !== $movie->getTmdbPosterPath()) {
                    $this->jobScheduler->storeMovieIdForTmdbImageCacheJob($movie->getId());
                }
            }

            $this->movieApi->updateGenres($movie->getId(), $this->genreConverter->getMovaryGenresFromTmdbMovie($tmdbMovie));
            $this->movieApi->updateProductionCompanies($movie->getId(), $this->productionCompanyConverter->getMovaryProductionCompaniesFromTmdbMovie($tmdbMovie));

            $this->movieApi->updateCast($movie->getId(), $tmdbMovie->getCredits()->getCast());
            $this->movieApi->updateCrew($movie->getId(), $tmdbMovie->getCredits()->getCrew());

            $this->dbConnection->commit();
        } catch (Exception $e) {
            $this->dbConnection->rollBack();

            throw $e;
        }

        if ($createdMovie === true) {
            $this->logger->debug('TMDB: Created movie meta data', ['movieId' => $movie->getId(), 'tmdbId' => $movie->getTmdbId()]);
        } else {
            $this->logger->debug('TMDB: Updated movie meta data', ['movieId' => $movie->getId(), 'tmdbId' => $movie->getTmdbId()]);
        }

        return $movie;
    }
}
