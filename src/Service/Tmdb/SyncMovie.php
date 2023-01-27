<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Doctrine\DBAL\Connection;
use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\JobQueue\JobQueueScheduler;
use Movary\ValueObject\Date;
use Throwable;

class SyncMovie
{
    private const SLEEP_AFTER_FIRST_FAILED_REQUEST_IN_MS = 1000000;

    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly MovieApi $movieApi,
        private readonly GenreConverter $genreConverter,
        private readonly ProductionCompanyConverter $productionCompanyConverter,
        private readonly Connection $dbConnection,
        private readonly JobQueueScheduler $jobScheduler,
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

        try {
            if ($movie === null) {
                $movie = $this->movieApi->create(
                    title: $tmdbMovie->getTitle(),
                    tmdbId: $tmdbId,
                    tagline: $tmdbMovie->getTagline(),
                    overview: $tmdbMovie->getOverview(),
                    originalLanguage: $tmdbMovie->getOriginalLanguage(),
                    releaseDate: Date::createFromDateTime($tmdbMovie->getReleaseDate()),
                    runtime: $tmdbMovie->getRuntime(),
                    tmdbVoteAverage: $tmdbMovie->getVoteAverage(),
                    tmdbVoteCount: $tmdbMovie->getVoteCount(),
                    tmdbPosterPath: $tmdbMovie->getPosterPath(),
                    tmdbBackdropPath: $tmdbMovie->getBackdropPath(),
                    imdbId: $tmdbMovie->getImdbId(),
                );

                $this->jobScheduler->storeMovieIdForTmdbImageCacheJob($movie->getId());
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

            $this->updateCredits($movie->getId(), $tmdbId);

            $this->dbConnection->commit();
        } catch (\Exception $e) {
            $this->dbConnection->rollBack();

            throw $e;
        }

        return $movie;
    }

    private function updateCredits(int $movieId, int $tmdbId) : void
    {
        $credits = $this->tmdbApi->fetchMovieCredits($tmdbId);

        $this->movieApi->updateCast($movieId, $credits->getCast());
        $this->movieApi->updateCrew($movieId, $credits->getCrew());
    }
}
