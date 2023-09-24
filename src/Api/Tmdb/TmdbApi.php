<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\Api\Tmdb\Cache\TmdbIsoLanguageCache;
use Movary\Api\Tmdb\Dto\TmdbCompany;
use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\Dto\TmdbPerson;
use Movary\Api\Tmdb\Dto\TmdbWatchProviderCollection;
use Movary\Api\Tmdb\Dto\TmdbWatchProviderList;
use Movary\ValueObject\Year;

class TmdbApi
{
    public function __construct(
        private readonly TmdbClient $client,
        private readonly TmdbIsoLanguageCache $iso6931,
    ) {
    }

    public function fetchCompany(int $companyId) : TmdbCompany
    {
        $data = $this->client->get('/company/' . $companyId);

        return TmdbCompany::createFromArray($data);
    }

    public function fetchMovieDetails(int $movieId) : TmdbMovie
    {
        $data = $this->client->get('/movie/' . $movieId, ['append_to_response' => 'credits']);

        return TmdbMovie::createFromArray($data);
    }

    public function fetchPersonDetails(int $personId) : TmdbPerson
    {
        $data = $this->client->get('/person/' . $personId);

        return TmdbPerson::createFromArray($data);
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

    public function searchMovie(string $searchTerm, ?Year $year = null, ?int $page = null) : array
    {
        $getParameters = ['query' => urlencode($searchTerm)];

        if ($year !== null) {
            $getParameters['year'] = (string)$year;
        }

        if ($page !== null) {
            $getParameters['page'] = (string)$page;
        }

        return $this->client->get('/search/movie', $getParameters);
    }
}
