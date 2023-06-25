<?php declare(strict_types=1);

namespace Movary\Api\Plex;

class PlexApi
{
    public function __construct(
        private readonly PlexTvClient $plexClient,
    ) {
    }

    public function findTmdbIdsOfWatchlistMovies(\Generator $plexWatchlistMovies, string $plexToken) : \Generator
    {
        foreach ($plexWatchlistMovies as $plexWatchlistMovie) {
            // TODO check if movie matching title and year already existing to save PLEX call?

            $movieData = $this->plexClient->get($plexWatchlistMovie['key'] . '?X-Plex-Token=' . $plexToken);

            if ($movieData['MediaContainer']['Metadata'][0]['type'] !== 'movie') {
                continue;
            }

            foreach ($movieData['MediaContainer']['Metadata'][0]['Guid'] as $guid) {
                if (str_starts_with($guid['id'], 'tmdb') === true) {
                    yield str_replace('tmdb://', '', $guid['id']);
                }
            }
            // TODO log if there is no tmdb found
        }
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
}
