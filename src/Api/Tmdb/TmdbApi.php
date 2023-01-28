<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\Api\Tmdb\Cache\TmdbIso6931Cache;
use Movary\Api\Tmdb\Dto\TmdbCompany;
use Movary\Api\Tmdb\Dto\TmdbCredits;
use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\Dto\TmdbPerson;

class TmdbApi
{
    public function __construct(
        private readonly TmdbClient $client,
        private readonly TmdbIso6931Cache $iso6931,
    ) {
    }

    public function fetchCompany(int $companyId) : TmdbCompany
    {
        $data = $this->client->get('/company/' . $companyId);

        return TmdbCompany::createFromArray($data);
    }

    public function fetchMovieCredits(int $movieId) : TmdbCredits
    {
        $data = $this->client->get('/movie/' . $movieId . '/credits');

        return TmdbCredits::createFromArray($data);
    }

    public function fetchMovieDetails(int $movieId) : TmdbMovie
    {
        $data = $this->client->get('/movie/' . $movieId);

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

    public function searchMovie(string $searchTerm) : array
    {
        $data = $this->client->get('/search/movie', ['query' => urlencode($searchTerm)]);

        return $data['results'];
    }
}
