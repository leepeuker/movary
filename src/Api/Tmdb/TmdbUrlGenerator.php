<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\ValueObject\Url;

class TmdbUrlGenerator
{
    public function generateImageUrl(string $path, string $size = 'w342') : Url
    {
        return Url::createFromString('https://image.tmdb.org/t/p/' . $size . '/' . trim($path, '/'));
    }

    public function generateMovieUrl(int $tmdbId) : string
    {
        return "https://www.themoviedb.org/movie/$tmdbId/";
    }
}
