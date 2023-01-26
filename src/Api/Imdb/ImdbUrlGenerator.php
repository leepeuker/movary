<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

class ImdbUrlGenerator
{
    public function buildMovieRatingsUrl(string $imdbId) : string
    {
        return "https://www.imdb.com/title/$imdbId/ratings";
    }

    public function buildMovieUrl(string $imdbId) : string
    {
        return "https://www.imdb.com/title/$imdbId";
    }
}
