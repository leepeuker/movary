<?php declare(strict_types=1);

namespace Movary\Api\Trakt;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Api\Trakt\ValueObject\User;

class Api
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getUserMovieHistoryByMovieId(string $username, TraktId $traktId) : User\Movie\History\DtoList
    {
        $responseData = $this->client->get(sprintf('/users/%s/history/movies/%d', $username, $traktId->asInt()));

        return User\Movie\History\DtoList::createFromArray($responseData);
    }

    public function getUserMoviesRatings(string $username) : User\Movie\Rating\DtoList
    {
        $responseData = $this->client->get(sprintf('/users/%s/ratings/movies', $username));

        return User\Movie\Rating\DtoList::createFromArray($responseData);
    }

    public function getUserMoviesWatched(string $username) : User\Movie\Watched\DtoList
    {
        $responseData = $this->client->get(sprintf('/users/%s/watched/movies', $username));

        return User\Movie\Watched\DtoList::createFromArray($responseData);
    }
}
