<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\Api\Tmdb\Dto\Credits;
use Movary\Api\Tmdb\Dto\Movie;

class Api
{
    private array $languages = [];

    public function __construct(private readonly Client $client)
    {
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
        if ($this->languages === []) {
            $this->languages = $this->client->get('/configuration/languages');
        }

        foreach ($this->languages as $language) {
            if ($language['iso_639_1'] === $languageCode) {
                return $language['english_name'];
            }
        }

        throw new \RuntimeException('Language code not handled: ' . $languageCode);
    }

    public function searchMovie(string $searchTerm) : array
    {
        $data = $this->client->get('/search/movie', ['query' => $searchTerm]);

        return $data['results'];
    }
}
