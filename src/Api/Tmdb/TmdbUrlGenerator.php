<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\ValueObject\Url;

class TmdbUrlGenerator
{
    public function generateImageUrl(string $path, string $size = 'w342') : Url
    {
        return Url::createFromString('https://image.tmdb.org/t/p/' . $size . '/' . trim($path, '/'));
    }

    public function generateMovieUrl(int $tmdbId) : Url
    {
        return Url::createFromString("https://www.themoviedb.org/movie/$tmdbId/");
    }

    public function generatePersonUrl(int $tmdbId) : Url
    {
        return Url::createFromString("https://www.themoviedb.org/person/$tmdbId/");
    }
}
