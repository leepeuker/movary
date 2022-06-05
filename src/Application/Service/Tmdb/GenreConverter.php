<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api\Tmdb;
use Movary\Application\Genre\Api;
use Movary\Application\Genre\EntityList;

class GenreConverter
{
    public function __construct(private readonly Api $genreApi)
    {
    }

    public function getMovaryGenresFromTmdbMovie(Tmdb\Dto\Movie $movieDetails) : EntityList
    {
        $genres = EntityList::create();

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
