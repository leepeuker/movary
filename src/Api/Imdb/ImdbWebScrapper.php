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

    public function findRating(string $imdbId) : array
    {
        $imdbMovieRatingPage = $this->fetchImdbMovieRatingPage($imdbId);
        // Removing whitespaces & linebreaks makes the regular expressions needed later easier
        $imdbMovieRatingPage = preg_replace('/\s*/m', '', $imdbMovieRatingPage);

        if ($imdbMovieRatingPage === null) {
            $this->logger->info('Could not find valid imdb movie rating page for: ' . $this->urlGenerator->buildMovieRatingsUrl($imdbId));

            return ['average' => null, 'voteCount' => null];
        }

        $ratingAverage = $this->getRatingAverage($imdbMovieRatingPage);
        if ($ratingAverage === null) {
            $this->logger->info('Could not find imdb rating average for: ' . $this->urlGenerator->buildMovieRatingsUrl($imdbId));

            return ['average' => $ratingAverage, 'voteCount' => null];
        }

        $ratingVoteCount = $this->getRatingVoteCount($imdbMovieRatingPage);
        if ($ratingVoteCount === null) {
            $this->logger->info('Could not find imdb rating vote count for: ' . $this->urlGenerator->buildMovieRatingsUrl($imdbId));
        }

        $this->logger->debug('Found complete imdb rating.', [
            'url' => $this->urlGenerator->buildMovieRatingsUrl($imdbId),
            'average' => $ratingAverage,
            'voteCount' => $ratingVoteCount ?? null
        ]);

        return ['average' => $ratingAverage, 'voteCount' => $ratingVoteCount ?? null];
    }

    private function fetchImdbMovieRatingPage(string $imdbId) : string
    {
        $response = $this->httpClient->get(
            $this->urlGenerator->buildMovieRatingsUrl($imdbId),
            [
                'headers' => ['User-Agent' => self::USER_AGENT],
            ],
        );

        return $response->getBody()->getContents();
    }

    private function getRatingAverage(string $responseBodyContent) : ?float
    {
        preg_match('/weightedaverage<\/a>voteof(\d.\d)\/10</', $responseBodyContent, $matchesAverage);
        if (empty($matchesAverage[1]) === false) {
            return (float)str_replace(['.', ','], ['', '.'], $matchesAverage[1]);
        }

        return null;
    }

    private function getRatingVoteCount(string $responseBodyContent) : ?int
    {
        preg_match('/([0-9]{1,3}([.,][0-9]{3})*)IMDbusershavegivena/', $responseBodyContent, $matchesAverage);
        if (empty($matchesAverage[1]) === false) {
            return (int)str_replace([',', '.'], '', $matchesAverage[1]);
        }

        return null;
    }
}
