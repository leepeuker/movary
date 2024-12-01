<?php declare(strict_types=1);

namespace Movary\Api\Letterboxd;

use GuzzleHttp\Client;
use RuntimeException;

class LetterboxdWebScrapper
{
    private const string BASE_URL = 'https://boxd.it/';

    private array $letterboxdIdToTmdbIdCache = [];

    public function __construct(private readonly Client $httpClient)
    {
    }

    public function scrapeLetterboxIdByDiaryUri(string $letterboxdDiaryUri) : string
    {
        $response = $this->httpClient->get($letterboxdDiaryUri);
        $responseBodyContent = $response->getBody()->getContents();

        preg_match('/analytic_params\[\'film_id\'] = \'([A-z0-9]+)\'/', $responseBodyContent, $letterboxdIdMatches);
        if (empty($letterboxdIdMatches[1]) === true) {
            throw new RuntimeException('Could not find letterboxd id on page: ' . $letterboxdDiaryUri);
        }

        return $letterboxdIdMatches[1];
    }

    public function scrapeTmdbIdByLetterboxdId(string $letterboxdId) : int
    {
        if (empty($this->letterboxdIdToTmdbIdCache[$letterboxdId]) === false) {
            return $this->letterboxdIdToTmdbIdCache[$letterboxdId];
        }

        $movieUrl = self::BASE_URL . $letterboxdId;

        $response = $this->httpClient->get($movieUrl);
        $responseBodyContent = $response->getBody()->getContents();

        preg_match('/data-tmdb-id=\"(\d+)\"/', $responseBodyContent, $tmdbIdMatches);
        if (empty($tmdbIdMatches[1]) === true) {
            preg_match('/film_id=\"(\d+)\"/', $responseBodyContent, $tmdbIdMatches);
            if (empty($tmdbIdMatches[1]) === true) {
                throw new RuntimeException('Could not find tmdb id on page: ' . $movieUrl);
            }
        }

        $tmdbId = (int)$tmdbIdMatches[1];

        $this->letterboxdIdToTmdbIdCache[$letterboxdId] = $tmdbId;

        return $tmdbId;
    }
}
