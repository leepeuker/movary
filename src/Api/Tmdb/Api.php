<?php declare(strict_types=1);

namespace Movary\Api\Tmdb;

use Movary\Api\Tmdb\Dto\Credits;
use Movary\Api\Tmdb\Dto\Movie;

class Api
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getMovieCredits(int $movieId) : Credits
    {
        $data = $this->client->get('/movie/' . $movieId . '/credits');

        return Credits::createFromArray($data);
    }

    public function getMovieDetails(int $movieId) : Movie
    {
        $data = $this->client->get('/movie/' . $movieId);

        return Movie::createFromArray($data);
    }
}
