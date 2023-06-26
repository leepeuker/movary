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
        $relativeUrl = 'library/sections/watchlist/all?X-Plex-Token=' . $plexToken;

        $limit = 20;
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

            $logPayload = [
                'plexTitle' => $moviePlexTitle,
                'plexYear' => (string)$moviePlexYear,
            ];

            $movie = $this->movieApi->findByTitleAndYear($moviePlexTitle, $moviePlexYear);

            $tmdbId = $movie?->getTmdbId();

            if ($tmdbId !== null) {
                yield $tmdbId;

                $this->logger->debug("Plex Api - Found tmdb id locally: " . $tmdbId, $logPayload);

                continue;
            }

            $movieData = $this->plexClient->get($plexWatchlistMovie['key'] . '?X-Plex-Token=' . $plexToken);

            if ($movieData['MediaContainer']['Metadata'][0]['type'] !== 'movie') {
                continue;
            }

            $foundTmdbId = false;

            foreach ($movieData['MediaContainer']['Metadata'][0]['Guid'] as $guid) {
                if (str_starts_with($guid['id'], 'tmdb') === true) {
                    $tmdbId = str_replace('tmdb://', '', $guid['id']);

                    yield $tmdbId;

                    $this->logger->debug("Plex Api - Found tmdb id on plex: " . $tmdbId, $logPayload);

                    $foundTmdbId = true;

                    break;
                }
            }

            if ($foundTmdbId === false) {
                $this->logger->debug("Plex Api - Could not find tmdb id", $logPayload);
            }
        }
    }
}
