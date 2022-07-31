<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

class UrlGenerator
{
    public function buildUrl(string $imdbId) : string
    {
        return "https://www.imdb.com/title/$imdbId/";
    }
}
