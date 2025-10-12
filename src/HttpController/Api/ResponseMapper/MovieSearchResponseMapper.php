<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\HttpController\Api\Dto\MovieSearchResultDto;
use Movary\HttpController\Api\Dto\MovieSearchResultDtoList;
use Movary\ValueObject\Date;

class MovieSearchResponseMapper
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
    )
    {
    }

    public function mapMovieSearchResults(int $userId, array $tmdbResponse) : MovieSearchResultDtoList
    {
        $searchResults = MovieSearchResultDtoList::create();
        $tmdbIds = [];

        foreach ($tmdbResponse['results'] as $result) {
            $searchResults->set($this->mapSearchResult($result));
            $tmdbIds[] = $result['id'];
        }

        $moviesExistingInMovary = $this->movieApi->findByTmdbIds($tmdbIds);

        $userWatchedMovies = $this->movieHistoryApi->fetchTmdbIdsToLastWatchDatesMap($userId, $tmdbIds);
        $userWatchlistMovies = $this->movieWatchlistApi->fetchTmdbIdsToWatchlistMap($userId, $tmdbIds);

        foreach ($moviesExistingInMovary as $movieExistingInMovary) {
            $searchResult = $searchResults->get($movieExistingInMovary->getTmdbId());
            $searchResult = $searchResult->withMovaryId($movieExistingInMovary->getId());

            if (isset($userWatchedMovies[$movieExistingInMovary->getTmdbId()]) === true) {
                $searchResult = $searchResult->withIsWatched(true);
            }

            if (isset($userWatchlistMovies[$movieExistingInMovary->getTmdbId()]) === true) {
                $searchResult = $searchResult->withIsOnWatchlist(true);
            }

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
