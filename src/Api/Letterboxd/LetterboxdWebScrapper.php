<?php declare(strict_types=1);

namespace Movary\Api\Letterboxd;

use Psr\Http\Client\ClientInterface;
use RuntimeException;

class LetterboxdWebScrapper
{
    public function __construct(private readonly ClientInterface $httpClient)
    {
    }

    public function getProviderTmdbId(string $letterboxedMovieUri) : int
    {
        $response = $this->httpClient->get($letterboxedMovieUri);
        $responseBodyContent = $response->getBody()->getContents();

        preg_match('/data-tmdb-id=\"(\d+)\"/', $responseBodyContent, $tmdbIdMatches);
        if (empty($tmdbIdMatches[1]) === true) {
            preg_match('/film_id=\"(\d+)\"/', $responseBodyContent, $tmdbIdMatches);
            if (empty($tmdbIdMatches[1]) === true) {
                throw new RuntimeException('Could not find tmdb id on page: ' . $letterboxedMovieUri);
            }
        }

        return (int)$tmdbIdMatches[1];
    }
}
