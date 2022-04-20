<?php declare(strict_types=1);

namespace Movary\Application\Service\Letterboxd;

use GuzzleHttp\Client;

class WebScrapper
{
    private Client $httpClient;

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getProviderTmdbId(string $letterboxedMovieUri) : int
    {
        $response = $this->httpClient->get($letterboxedMovieUri);
        $responseBodyContent = $response->getBody()->getContents();

        preg_match('/data-tmdb-id=\"(\d+)\"/', $responseBodyContent, $tmdbIdMatches);
        if (empty($tmdbIdMatches[1]) === true) {
            throw new \RuntimeException('Could not find tmdb id on page: ' . $letterboxedMovieUri);
        }

        return (int)$tmdbIdMatches[1];
    }
}