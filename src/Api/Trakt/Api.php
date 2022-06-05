<?php declare(strict_types=1);

namespace Movary\Api\Trakt;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Api\Trakt\ValueObject\User;

class Api
{
    public function __construct(
        private readonly Client $client,
        private readonly string $username
    ) {
    }

    public function fetchUserMovieHistoryByMovieId(TraktId $traktId) : User\Movie\History\DtoList
    {
        $responseData = $this->client->get(sprintf('/users/%s/history/movies/%d', $this->username, $traktId->asInt()));

        return User\Movie\History\DtoList::createFromArray($responseData);
    }

    public function fetchUserMoviesRatings() : User\Movie\Rating\DtoList
    {
        $responseData = $this->client->get(sprintf('/users/%s/ratings/movies', $this->username));

        return User\Movie\Rating\DtoList::createFromArray($responseData);
    }

    public function fetchUserMoviesWatched() : User\Movie\Watched\DtoList
    {
        $responseData = $this->client->get(sprintf('/users/%s/watched/movies', $this->username));

        return User\Movie\Watched\DtoList::createFromArray($responseData);
    }
}
