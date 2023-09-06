<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\HttpController\Api\Dto\MovieDto;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;

class MovieResponseMapper
{
    public function mapMovie(array $movieData) : MovieDto
    {
        return MovieDto::create(
            (int)$movieData['id'],
            $movieData['title'],
            (int)$movieData['tmdb_id'],
            DateTime::createFromString($movieData['created_at']),
            empty($movieData['trakt_id']) === false ? TraktId::createFromString((string)$movieData['trakt_id']) : null,
            $movieData['imdb_id'],
            $movieData['letterboxd_id'],
            $movieData['tagline'],
            $movieData['overview'],
            $movieData['original_language'],
            $movieData['runtime'] === null ? null : (int)$movieData['runtime'],
            $movieData['release_date'] === null ? null : Date::createFromString($movieData['release_date']),
            $movieData['poster_path'],
            $movieData['tmdb_vote_average'] === null ? null : (float)$movieData['tmdb_vote_average'],
            $movieData['tmdb_vote_count'] === null ? null : (int)$movieData['tmdb_vote_count'],
            $movieData['imdb_rating_average'] === null ? null : (float)$movieData['imdb_rating_average'],
            $movieData['imdb_rating_vote_count'] === null ? null : (int)$movieData['imdb_rating_vote_count'],
            $movieData['updated_at'] === null ? null : DateTime::createFromString($movieData['updated_at']),
            $movieData['userRating'],
        );
    }
}
