<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api\Tmdb;
use Movary\Application\Genre\GenreApi;
use Movary\Application\Genre\GenreEntityList;

class GenreConverter
{
    public function __construct(private readonly GenreApi $genreApi)
    {
    }

    public function getMovaryGenresFromTmdbMovie(Tmdb\Dto\Movie $movieDetails) : GenreEntityList
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
