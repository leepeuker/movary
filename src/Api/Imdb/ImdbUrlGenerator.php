<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

class ImdbUrlGenerator
{
    public function buildUrl(string $imdbId) : string
    {
        return "https://www.imdb.com/title/$imdbId/";
    }
}
