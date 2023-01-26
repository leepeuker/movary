<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use GuzzleHttp\Client;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\ImdbRating;
use Psr\Log\LoggerInterface;

class ImdbWebScrapper
{
    private const REQUEST_HEADERS = ['headers' => ['User-Agent' => self::USER_AGENT]];

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:107.0) Gecko/20100101 Firefox/107.0';

    public function __construct(
        private readonly Client $httpClient,
        private readonly ImdbUrlGenerator $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findRating(string $imdbId) : ?ImdbRating
    {
        $imdbMovieRatingPage = $this->findImdbMovieRatingPageContent($imdbId);
        if ($imdbMovieRatingPage === null) {
            return null;
        }

        $ratingAverage = $this->extractRatingAverage($imdbMovieRatingPage, $imdbId);
        if ($ratingAverage === null) {
            return null;
        }

        $ratingVoteCount = $this->extractRatingVoteCount($imdbMovieRatingPage, $imdbId);
        if ($ratingVoteCount === null) {
            return null;
        }

        $imdbRating = ImdbRating::create($ratingAverage, $ratingVoteCount);

        $this->logger->debug('IMDb: Found movie rating.', [
            'url' => $this->urlGenerator->buildMovieRatingsUrl($imdbId),
            'average' => $imdbRating->getRating(),
            'voteCount' => $imdbRating->getVotesCount(),
        ]);

        return $imdbRating;
    }

    private function extractRatingAverage(string $imdbRatingPage, string $imdbId) : ?float
    {
        preg_match('/weightedaverage<\/a>voteof(\d.\d)\/10</', $imdbRatingPage, $averageRatingMatches);
        if (empty($averageRatingMatches[1]) === true) {
            $this->logger->warning('IMDb: Could not extract rating average.', ['url' => $this->urlGenerator->buildMovieRatingsUrl($imdbId)]);

            return null;
        }

        return (float)str_replace(',', '.', $averageRatingMatches[1]);
    }

    private function extractRatingVoteCount(string $imdbRatingPage, string $imdbId) : ?int
    {
        preg_match('/([0-9]{1,3}([.,][0-9]{3})*)IMDbusershavegivena/', $imdbRatingPage, $voteCountMatches);
        if (empty($voteCountMatches[1]) === true) {
            $this->logger->warning('IMDb: Could not extract imdb rating vote count.', ['url' => $this->urlGenerator->buildMovieRatingsUrl($imdbId)]);

            return null;
        }

        return (int)str_replace([',', '.'], '', $voteCountMatches[1]);
    }

    private function findImdbMovieRatingPageContent(string $imdbId) : ?string
    {
        $url = $this->urlGenerator->buildMovieRatingsUrl($imdbId);

        $response = $this->httpClient->get($url, self::REQUEST_HEADERS);

        if ($response->getStatusCode() !== StatusCode::createOk()->getCode()) {
            $this->logger->warning('IMDb: Could not fetch movie rating page.', ['url' => $url]);

            return null;
        }

        // Removing whitespaces & linebreaks makes the regular expressions needed later easier
        $pageContentSanitized = preg_replace('/\s*/m', '', $response->getBody()->getContents());

        if (is_string($pageContentSanitized) === false) {
            $this->logger->warning('IMDb: Fetched movie rating page has invalid content.', ['url' => $url]);

            return null;
        }

        return $pageContentSanitized;
    }
}
