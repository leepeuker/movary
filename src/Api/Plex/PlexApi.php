<?php declare(strict_types=1);

namespace Movary\Api\Plex;

use Movary\Domain\Movie\MovieApi;
use Movary\ValueObject\Year;
use Psr\Log\LoggerInterface;

class PlexApi
{
    public function __construct(
        private readonly PlexTvClient $plexClient,
        private readonly MovieApi $movieApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchWatchlist(string $plexToken) : \Generator
    {
        $queryParameters = [
            'type' => '1',
            'includeFields' => 'title%2Ctype%2Cyear%2Ckey',
            'includeElements' => 'Guid',
            'sort' => 'watchlistedAt%3Adesc',
            'X-Plex-Token' => $plexToken,
        ];

        $queryString = '';
        foreach ($queryParameters as $key => $value) {
            $queryString .= "$key=$value&";
        }

        $relativeUrl = 'library/sections/watchlist/all?' . $queryString;

        $limit = 30;
        $offset = 0;

        do {
            $responseData = $this->plexClient->get($relativeUrl, $limit, $offset);

            $offset += $limit;

            $totalItems = $responseData['MediaContainer']['totalSize'];

            foreach ($responseData['MediaContainer']['Metadata'] as $movie) {
                yield $movie;
            }
        } while ($totalItems > $offset);
    }

    public function findTmdbIdsOfWatchlistMovies(\Generator $plexWatchlistMovies, string $plexToken) : \Generator
    {
        foreach ($plexWatchlistMovies as $plexWatchlistMovie) {
            $moviePlexTitle = $plexWatchlistMovie['title'];
            $moviePlexYear = Year::createFromInt($plexWatchlistMovie['year']);

            $movie = $this->movieApi->findByTitleAndYear($moviePlexTitle, $moviePlexYear);

            $tmdbId = $movie?->getTmdbId();

            if ($tmdbId !== null) {
                yield $tmdbId;

                $this->logger->debug(
                    'Plex Api - Found tmdb id locally',
                    [
                        'tmdbId' => (string)$tmdbId,
                        'plexTitle' => $moviePlexTitle,
                        'plexYear' => (string)$moviePlexYear,
                    ],
                );

                continue;
            }

            $movieData = $this->plexClient->get($plexWatchlistMovie['key'] . '?X-Plex-Token=' . $plexToken);

            $tmdbId = null;

            foreach ($movieData['MediaContainer']['Metadata'][0]['Guid'] as $guid) {
                if (str_starts_with($guid['id'], 'tmdb') === true) {
                    $tmdbId = str_replace('tmdb://', '', $guid['id']);

                    yield $tmdbId;

                    $this->logger->debug(
                        'Plex Api - Found tmdb id on plex',
                        [
                            'tmdbId' => $tmdbId,
                            'plexTitle' => $moviePlexTitle,
                            'plexYear' => (string)$moviePlexYear,
                        ],
                    );

                    break;
                }
            }

            if ($tmdbId === null) {
                $this->logger->debug(
                    'Plex Api - Could not find tmdb id',
                    [
                        'plexTitle' => $moviePlexTitle,
                        'plexYear' => (string)$moviePlexYear,
                    ],
                );
            }
        }
    }
}
