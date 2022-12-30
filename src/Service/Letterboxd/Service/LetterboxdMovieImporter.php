<?php declare(strict_types=1);

namespace Movary\Service\Letterboxd\Service;

use Movary\Api\Letterboxd\LetterboxdWebScrapper;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Service\Tmdb\SyncMovie;

class LetterboxdMovieImporter
{
    public function __construct(
        private readonly LetterboxdMovieFinder $movieFinder,
        private readonly LetterboxdWebScrapper $webScrapper,
        private readonly LetterboxdDiaryCache $diaryCache,
        private readonly SyncMovie $tmdbMovieSync,
        private readonly MovieApi $movieApi,
    ) {
    }

    public function importMovieByDiaryUri(string $letterboxdDiaryUri) : MovieEntity
    {
        $letterboxdId = $this->getLetterboxIdByDiaryUri($letterboxdDiaryUri);

        $movie = $this->movieFinder->findMovieLocally($letterboxdId);

        if ($movie === null) {
            $movie = $this->createMovie($letterboxdId);

            $this->movieApi->updateLetterboxdId($movie->getId(), $letterboxdId);
        }

        return $movie;
    }

    private function createMovie(string $letterboxdId) : MovieEntity
    {
        $tmdbId = $this->webScrapper->scrapeTmdbIdByLetterboxdId($letterboxdId);

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSync->syncMovie($tmdbId);
        }

        return $movie;
    }

    private function getLetterboxIdByDiaryUri(string $letterboxdDiaryUri) : string
    {
        $diaryId = basename($letterboxdDiaryUri);

        $letterboxdId = $this->diaryCache->findLetterboxdIdToDiaryUri($diaryId);

        if ($letterboxdId !== null) {
            return $letterboxdId;
        }

        $letterboxdId = $this->webScrapper->scrapeLetterboxIdByDiaryUri($letterboxdDiaryUri);

        $this->diaryCache->setLetterboxdIdToDiaryUri($diaryId, $letterboxdId);

        return $letterboxdId;
    }
}
