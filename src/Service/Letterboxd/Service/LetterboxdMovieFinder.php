<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd\Service;

use Exception;
use Movary\Api\Letterboxd\LetterboxdWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;

class LetterboxdMovieFinder
{
    public function __construct(
        private readonly LetterboxdWebScrapper $webScrapper,
        private readonly MovieApi $movieApi,
    ) {
    }

    public function findMovieLocally(string $letterboxdId) : ?MovieEntity
    {
        $movie = $this->movieApi->findByLetterboxdId($letterboxdId);
        if ($movie !== null) {
            return $movie;
        }

        try {
            $tmdbId = $this->webScrapper->scrapeTmdbIdByLetterboxdId($letterboxdId);
        } catch (Exception $e) {
            return null;
        }

        $movie = $this->movieApi->findByTmdbId($tmdbId);
        if ($movie === null) {
            return null;
        }

        $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);

        return $movie;
    }
}
