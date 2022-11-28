<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\Api\Tmdb\Cache\TmdbIso6931Cache;
use Movary\Api\Tmdb\Dto\Company;
use Movary\Api\Tmdb\Dto\Credits;
use Movary\Api\Tmdb\Dto\Movie;

class Api
{
    public function __construct(
        private readonly Client $client,
        private readonly TmdbIso6931Cache $iso6931,
    ) {
    }

    public function fetchCompany(int $companyId) : Company
    {
        $data = $this->client->get('/company/' . $companyId);

        return Company::createFromArray($data);
    }

    public function fetchMovieCredits(int $movieId) : Credits
    {
        $data = $this->client->get('/movie/' . $movieId . '/credits');

        return Credits::createFromArray($data);
    }

    public function fetchMovieDetails(int $movieId) : Movie
    {
        $data = $this->client->get('/movie/' . $movieId);

        return Movie::createFromArray($data);
    }

    public function getLanguageByCode(string $languageCode) : string
    {
        return $this->iso6931->getLanguageByCode($languageCode);
    }

    public function searchMovie(string $searchTerm) : array
    {
        $data = $this->client->get('/search/movie', ['query' => $searchTerm]);

        return $data['results'];
    }
}
