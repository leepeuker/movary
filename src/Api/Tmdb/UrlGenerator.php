<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

class UrlGenerator
{
    public function buildUrl(int $tmdbId) : string
    {
        return "https://www.themoviedb.org/movie/$tmdbId/";
    }
}
