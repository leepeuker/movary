<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api\Tmdb;
use Movary\Application\Movie;
use Movary\ValueObject\Date;

class SyncMovie
{
    public function __construct(
        private readonly Tmdb\Api $tmdbApi,
        private readonly Movie\Api $movieApi,
        private readonly GenreConverter $genreConverter,
        private readonly ProductionCompanyConverter $productionCompanyConverter,
    ) {
    }

    public function syncMovie(int $tmdbId) : Movie\Entity
    {
        $tmdbMovie = $this->tmdbApi->fetchMovieDetails($tmdbId);

        $movie = $this->movieApi->findByTmdbId($tmdbId);

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
            );
        } else {
            $movie = $this->movieApi->updateDetails(
                $movie->getId(),
                $tmdbMovie->getTagline(),
                $tmdbMovie->getOverview(),
                $tmdbMovie->getOriginalLanguage(),
                $tmdbMovie->getReleaseDate(),
                $tmdbMovie->getRuntime(),
                $tmdbMovie->getVoteAverage(),
                $tmdbMovie->getVoteCount(),
                $tmdbMovie->getPosterPath(),
            );
        }

        $this->movieApi->updateGenres($movie->getId(), $this->genreConverter->getMovaryGenresFromTmdbMovie($tmdbMovie));
        $this->movieApi->updateProductionCompanies($movie->getId(), $this->productionCompanyConverter->getMovaryProductionCompaniesFromTmdbMovie($tmdbMovie));

        $this->updateCredits($movie->getId(), $tmdbId);

        return $movie;
    }

    private function updateCredits(int $movieId, int $tmdbId) : void
    {
        $credits = $this->tmdbApi->fetchMovieCredits($tmdbId);

        $this->movieApi->updateCast($movieId, $credits->getCast());
        $this->movieApi->updateCrew($movieId, $credits->getCrew());
    }
}
