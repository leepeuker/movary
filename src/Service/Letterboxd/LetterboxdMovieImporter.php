<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd;

use Movary\Api\Letterboxd\LetterboxdWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Service\Tmdb\SyncMovie;

class LetterboxdMovieImporter
{
    public function __construct(
        private readonly LetterboxdWebScrapper $webScrapper,
        private readonly SyncMovie $tmdbMovieSync,
        private readonly MovieApi $movieApi,
    ) {
    }

    public function importMovieByLetterboxdUri(string $letterboxdUri) : MovieEntity
    {
        $letterboxdId = basename($letterboxdUri);
        $movie = $this->movieApi->findByLetterboxdId($letterboxdId);

        if ($movie === null) {
            $movie = $this->createMovie($letterboxdUri);

            $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);
        }

        return $movie;
    }

    private function createMovie(string $letterboxdUri) : MovieEntity
    {
        $tmdbId = $this->webScrapper->getProviderTmdbId($letterboxdUri);

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSync->syncMovie($tmdbId);
        }

        return $movie;
    }
}
