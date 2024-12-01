<?php declare(strict_types=1);

namespace Movary\Api\Imdb;

use GuzzleHttp\Client;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\ImdbRating;
use Psr\Log\LoggerInterface;

class ImdbWebScrapper
{
    private const array REQUEST_HEADERS = ['headers' => ['User-Agent' => self::USER_AGENT]];

    private const string USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:111.0) Gecko/20100101 Firefox/111.0';

    private const string VOTING_AVERAGE_CLASS_NAME = 'imUuxf';

    private const string VOTING_COUNT_CLASS_NAME = 'dwhNqC';

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

        $productionStatus = $this->extractProductionStatus($imdbMovieRatingPage);
        if ($productionStatus !== null) {
            $this->logger->debug('IMDb: Ignoring not yet released movie', [
                'url' => $this->urlGenerator->buildMovieUrl($imdbId),
                'productionStatus' => $productionStatus,
            ]);

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

        $this->logger->debug('IMDb: Found movie rating', [
            'url' => $this->urlGenerator->buildMovieUrl($imdbId),
            'average' => $imdbRating->getRating(),
            'voteCount' => $imdbRating->getVotesCount(),
        ]);

        return $imdbRating;
    }

    private function extractProductionStatus(string $imdbRatingPage) : ?string
    {
        preg_match('~hjAonB">([^<]*)~', $imdbRatingPage, $productionStatus);
        if (empty($productionStatus[1]) === true) {
            return null;
        }

        return $productionStatus[1];
    }

    private function extractRatingAverage(string $imdbRatingPage, string $imdbId) : ?float
    {
        preg_match('/' . self::VOTING_AVERAGE_CLASS_NAME . '">(\d([.,])\d)/', $imdbRatingPage, $averageRatingMatches);
        if (empty($averageRatingMatches[1]) === true) {
            $this->logger->warning('IMDb: Could not extract rating average.', ['url' => $this->urlGenerator->buildMovieUrl($imdbId)]);

            return null;
        }

        return (float)str_replace(',', '.', $averageRatingMatches[1]);
    }

    private function extractRatingVoteCount(string $imdbRatingPage, string $imdbId) : ?int
    {
        // Handle numbers without suffix
        preg_match('/' . self::VOTING_COUNT_CLASS_NAME . '">([0-9]+)</', $imdbRatingPage, $voteCountMatches);
        if (empty($voteCountMatches[1]) === false) {
            return (int)$voteCountMatches[1];
        }
        preg_match('/' . self::VOTING_COUNT_CLASS_NAME . '">([0-9]{1,3}([.,]?[0-9]{3})+)/', $imdbRatingPage, $voteCountMatches);
        if (empty($voteCountMatches[1]) === false) {
            return (int)str_replace([',', '.'], '', $voteCountMatches[1]);
        }

        // Handle numbers with K suffix
        preg_match('/' . self::VOTING_COUNT_CLASS_NAME . '">([0-9]+)K</', $imdbRatingPage, $voteCountMatches);
        if (empty($voteCountMatches[1]) === false) {
            return (int)$voteCountMatches[1] * 1000;
        }
        preg_match('/' . self::VOTING_COUNT_CLASS_NAME . '">([0-9]{1,3}[.,][0-9]{1,3})K</', $imdbRatingPage, $voteCountMatches);
        if (empty($voteCountMatches[1]) === false) {
            return (int)((float)$voteCountMatches[1] * 1000);
        }

        // Handle simple numbers with M suffix
        preg_match('/' . self::VOTING_COUNT_CLASS_NAME . '">([0-9]+)M</', $imdbRatingPage, $voteCountMatches);
        if (empty($voteCountMatches[1]) === false) {
            return (int)$voteCountMatches[1] * 1000000;
        }
        // Handle simple numbers with K suffix
        preg_match('/' . self::VOTING_COUNT_CLASS_NAME . '">([0-9]{1,3}[.,][0-9]{1,3})M</', $imdbRatingPage, $voteCountMatches);
        if (empty($voteCountMatches[1]) === false) {
            return (int)((float)$voteCountMatches[1] * 1000000);
        }

        $this->logger->warning('IMDb: Could not extract imdb rating vote count.', ['url' => $this->urlGenerator->buildMovieUrl($imdbId)]);

        return null;
    }

    private function findImdbMovieRatingPageContent(string $imdbId) : ?string
    {
        $url = $this->urlGenerator->buildMovieUrl($imdbId);

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
