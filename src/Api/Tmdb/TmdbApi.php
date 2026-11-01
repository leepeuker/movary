<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\Api\Tmdb\Cache\TmdbIsoLanguageCache;
use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\Dto\TmdbPerson;
use Movary\Api\Tmdb\Dto\TmdbPoster;
use Movary\Api\Tmdb\Dto\TmdbProfile;
use Movary\Api\Tmdb\Dto\TmdbWatchProviderCollection;
use Movary\Api\Tmdb\Dto\TmdbWatchProviderList;
use Movary\Api\Tmdb\Exception\TmdbResourceNotFound;
use Movary\ValueObject\Year;

class TmdbApi
{
    public function __construct(
        private readonly TmdbClient $client,
        private readonly TmdbIsoLanguageCache $iso6931,
        private readonly TmdbUrlGenerator $urlGenerator,
    ) {
    }

    public function fetchMovieDetails(int $movieId) : TmdbMovie
    {
        $data = $this->client->get('/movie/' . $movieId, ['append_to_response' => 'credits']);

        return TmdbMovie::createFromArray($data);
    }

    public function fetchPersonDetails(int $personId) : TmdbPerson
    {
        $data = $this->client->get('/person/' . $personId, ['append_to_response' => 'external_ids']);

        return TmdbPerson::createFromArray($data);
    }

    /** @return list<TmdbProfile> */
    public function fetchPersonProfiles(int $personId) : array
    {
        $data = $this->client->get('/person/' . $personId . '/images');

        $profiles = [];
        foreach ($data['profiles'] ?? [] as $profile) {
            $path = $profile['file_path'] ?? null;
            if (is_string($path) === false || $path === '') {
                continue;
            }

            $profiles[] = new TmdbProfile(
                $path,
                (string)$this->urlGenerator->generateImageUrl($path, 'w154'),
            );
        }

        return $profiles;
    }

    /** @return list<TmdbPoster> */
    public function fetchEnglishMoviePosters(int $movieId) : array
    {
        $data = $this->client->get(
            '/movie/' . $movieId . '/images',
            ['language' => 'en-US', 'include_image_language' => 'en'],
        );

        $posters = [];
        foreach ($data['posters'] ?? [] as $poster) {
            $path = $poster['file_path'] ?? null;
            if (is_string($path) === false || $path === '') {
                continue;
            }

            $posters[] = new TmdbPoster(
                $path,
                (string)$this->urlGenerator->generateImageUrl($path, 'w154'),
            );
        }

        return $posters;
    }

    public function getLanguageByCode(string $languageCode) : string
    {
        return $this->iso6931->getLanguageByCode($languageCode);
    }

    public function getWatchProviders(int $tmdbId, string $country) : TmdbWatchProviderCollection
    {
        $data = $this->client->get("/movie/$tmdbId/watch/providers", ['append_to_response' => 'credits']);

        $data = $data['results'][$country] ?? [];

        return TmdbWatchProviderCollection::create(
            TmdbWatchProviderList::createFromArray($data['flatrate'] ?? []),
            TmdbWatchProviderList::createFromArray($data['rent'] ?? []),
            TmdbWatchProviderList::createFromArray($data['buy'] ?? []),
            TmdbWatchProviderList::createFromArray($data['ads'] ?? []),
            TmdbWatchProviderList::createFromArray($data['free'] ?? []),
        );
    }

    /** searchMovie — directly exposes data in TMDB format to frontend given a search query */
    public function searchMovie(string $searchTerm, ?Year $year = null, ?int $page = null) : array
    {
        $searchTermContainsTmdbId = preg_match('#themoviedb.org/movie/(\\d+)($|-)#i', $searchTerm, $tmdbIdsMatches);

        if ($searchTermContainsTmdbId === 1) {
            $tmdbId = (int)$tmdbIdsMatches[1];

            if ($tmdbId === 0) {
                return ['results' => []];
            }

            $movie = $this->findMovie($tmdbId);

            if (count($movie) === 0) {
                return ['results' => []];
            }

            return [
                'results' => [$this->findMovie($tmdbId)]
            ];
        }

        $getParameters = ['query' => urlencode($searchTerm)];

        if ($year !== null) {
            $getParameters['year'] = (string)$year;
        }

        if ($page !== null) {
            $getParameters['page'] = (string)$page;
        }

        return $this->client->get('/search/movie', $getParameters);
    }

    private function findMovie(int $movieId) : array
    {
        try {
            return $this->client->get('/movie/' . $movieId);
        } catch (TmdbResourceNotFound) {
            return [];
        }
    }
}
