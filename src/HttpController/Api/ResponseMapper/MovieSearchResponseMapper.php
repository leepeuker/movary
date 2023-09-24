<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\Dto\MovieSearchResultDto;
use Movary\HttpController\Api\Dto\MovieSearchResultDtoList;
use Movary\ValueObject\Date;

class MovieSearchResponseMapper
{
    public function __construct(private readonly MovieApi $movieApi)
    {
    }

    public function mapMovieSearchResults(array $tmdbResponse) : MovieSearchResultDtoList
    {
        $searchResults = MovieSearchResultDtoList::create();
        $tmdbIds = [];

        foreach ($tmdbResponse['results'] as $result) {
            $searchResults->set($this->mapSearchResult($result));
            $tmdbIds[] = $result['id'];
        }

        foreach ($this->movieApi->findByTmdbIds($tmdbIds) as $movie) {
            $searchResult = $searchResults->get($movie->getTmdbId());
            $searchResult = $searchResult->withMovaryId($movie->getId());

            $searchResults->set($searchResult);
        }

        return $searchResults;
    }

    private function mapSearchResult(array $tmdbResponse) : MovieSearchResultDto
    {
        return MovieSearchResultDto::create(
            $tmdbResponse['id'],
            $tmdbResponse['title'],
            $tmdbResponse['overview'],
            empty($tmdbResponse['release_date']) === true ? null : Date::createFromString($tmdbResponse['release_date']),
            $tmdbResponse['original_language'],
            $tmdbResponse['poster_path'],
        );
    }
}
