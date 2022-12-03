<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Movary\Api\Tmdb;
use Movary\Domain\Genre\GenreApi;
use Movary\Domain\Genre\GenreEntityList;

class GenreConverter
{
    public function __construct(private readonly GenreApi $genreApi)
    {
    }

    public function getMovaryGenresFromTmdbMovie(Tmdb\Dto\TmdbMovie $movieDetails) : GenreEntityList
    {
        $genres = GenreEntityList::create();

        foreach ($movieDetails->getGenres() as $tmdbGenre) {
            $genre = $this->genreApi->findByTmdbId($tmdbGenre->getId());

            if ($genre === null) {
                $genre = $this->genreApi->create($tmdbGenre->getName(), $tmdbGenre->getId());
            }

            $genres->add($genre);
        }

        return $genres;
    }
}
