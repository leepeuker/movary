<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class WebScrapper
{
    public function __construct(private readonly Client $httpClient, private readonly LoggerInterface $logger)
    {
    }

    public function findRating(string $imdbId) : ?float
    {
        $response = $this->httpClient->get("https://www.imdb.com/title/$imdbId/");
        $responseBodyContent = $response->getBody()->getContents();

        preg_match('/sc-7ab21ed2-1 jGRxWM">(\d.\d)</', $responseBodyContent, $matches);
        if (empty($matches[1]) === true) {
            $this->logger->info('Could not find imdb rating for: ' . "https://www.imdb.com/title/$imdbId/");

            return null;
        }

        return (float)$matches[1];
    }
}
