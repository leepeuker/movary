<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class ImdbWebScrapper
{
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:107.0) Gecko/20100101 Firefox/107.0';

    public function __construct(
        private readonly Client $httpClient,
        private readonly ImdbUrlGenerator $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    // phpcs:ignore
    public function findRating(string $imdbId) : array
    {
        $rating = ['average' => null, 'voteCount' => null];

        $response = $this->httpClient->get(
            $this->urlGenerator->buildUrl($imdbId),
            [
                'headers' => ['User-Agent' => self::USER_AGENT],
            ],
        );

        $responseBodyContent = $response->getBody()->getContents();

        preg_match('/sc-7ab21ed2-1 jGRxWM">(\d.\d)</', $responseBodyContent, $matchesAverage);
        if (empty($matchesAverage[1]) === true) {
            $this->logger->info('Could not find imdb rating average for: ' . "https://www.imdb.com/title/$imdbId/");
        } else {
            $rating['average'] = (float)$matchesAverage[1];
        }

        preg_match('/sc-7ab21ed2-3 dPVcnq">(\d+)</', $responseBodyContent, $matchesVoteCount);
        if (empty($matchesVoteCount[1]) === true) {
            preg_match('/sc-7ab21ed2-3 dPVcnq">(\d+)K</', $responseBodyContent, $matchesVoteCount);
            if (empty($matchesVoteCount[1]) === true) {
                preg_match('/sc-7ab21ed2-3 dPVcnq">(\d+\.\d)K</', $responseBodyContent, $matchesVoteCount);
                if (empty($matchesVoteCount[1]) === true) {
                    preg_match('/sc-7ab21ed2-3 dPVcnq">(\d+\.\d)M</', $responseBodyContent, $matchesVoteCount);
                    if (empty($matchesVoteCount[1]) === true) {
                        preg_match('/sc-7ab21ed2-3 dPVcnq">(\d+)M</', $responseBodyContent, $matchesVoteCount);
                        if (empty($matchesVoteCount[1]) === true) {
                            $this->logger->info('Could not find imdb rating vote count for: ' . "https://www.imdb.com/title/$imdbId/");
                        } else {
                            $rating['voteCount'] = (int)((float)$matchesVoteCount[1] * 1000000);
                        }
                    } else {
                        $rating['voteCount'] = (int)((float)$matchesVoteCount[1] * 1000000);
                    }
                } else {
                    $rating['voteCount'] = (int)((float)$matchesVoteCount[1] * 1000);
                }
            } else {
                $rating['voteCount'] = (int)$matchesVoteCount[1] * 1000;
            }
        } else {
            $rating['voteCount'] = (int)$matchesVoteCount[1];
        }

        return $rating;
    }
}
