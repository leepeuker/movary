<?php declare(strict_types=1);

namespace Movary\Service\Netflix;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Service\Netflix\Dto\NetflixActivityItemList;
use Psr\Log\LoggerInterface;

class NetflixActivityItemsConverter
{
    private const array SHOW_PATTERNS = [
        // Check for TvShow: Season 1: EpisodeTitle
        "(.+): .+ (\d{1,2}): (.*)",
        // Check for TvShow: Season 1 - Part A: EpisodeTitle
        "(.+): .+ (\d{1,2}) - .+: (.*)",
        // Check for TvShow: TvShow : Miniseries : EpisodeTitle
        "(.+): \w+: (.+)",
        // Check for TvShow: SeasonName : EpisodeTitle
        "(.+): (.+): (.+)",
        // Check for TvShow: EpisodeTitle
        "(.+): (.+)"
    ];

    public function __construct(
        private readonly TmdbApi $tmdbApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function convertActivityItemsToTmdbMatches(NetflixActivityItemList $activityItems) : array
    {
        $tmdbSearchResults = [];
        foreach ($activityItems as $activityItem) {
            if (trim($activityItem->getTitle()) == '') {
                $this->logger->debug('Netflix: Ignoring item because it has no valid title', ['netflixTitle' => $activityItem->getTitle()]);
                continue;
            }
            $mediaData = $this->extractMediaDataFromTitle($activityItem->getTitle());

            if ($mediaData['type'] !== 'movie') {
                $this->logger->info('Netflix: Ignoring item because it is no movie', ['netflixTitle' => $activityItem->getTitle()]);

                continue;
            }

            $movieName = $mediaData['movieName'];

            $search = $this->tmdbApi->searchMovie($movieName);
            $tmdbSearchResults[] = [
                'tmdbMatch' => $search['results'][0] ?? null,
                'netflixWatchDate' => (string)$activityItem->getDate(),
                'netflixMovieName' => $movieName
            ];

            $this->logger->info('Netflix: Found tmdb match for movie', ['netflixTitle' => $activityItem->getTitle()]);
        }

        return $tmdbSearchResults;
    }

    private function extractMediaDataFromTitle(string $title) : array
    {
        $mediaData = null;

        foreach (self::SHOW_PATTERNS as $showPattern) {
            $mediaData = $this->extractShowDataFromTitle($title, $showPattern);
        }

        if ($mediaData !== null) {
            return $mediaData;
        }

        return [
            'movieName' => $title,
            'type' => 'movie'
        ];
    }

    private function extractShowDataFromTitle(string $title, string $pattern) : ?array
    {
        $result = preg_match_all("/$pattern/m", $title, $matches);
        if ((int)$result < 1) {
            return null;
        }

        if (count($matches) > 3) {
            $seasonNumber = $matches[2];
            $episodeTitle = $matches[3];
        } else {
            $seasonNumber = 1;
            $episodeTitle = $matches[2];
        }

        return [
            'showName' => $matches[1],
            'seasonNumber' => $seasonNumber,
            'episodeTitle' => $episodeTitle,
            'type' => 'show'
        ];
    }
}
