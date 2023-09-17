<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\Domain\Movie\MovieApi;

class MovieSearchResponseMapper
{
    public function __construct(private readonly MovieApi $movieApi)
    {
    }

    public function mapMovieSearchResults(array $tmdbResponse) : array
    {
        $searchResults = [];
        $tmdbIds = [];

        foreach ($tmdbResponse['results'] as $result) {
            $searchResults[$result['id']] = $this->mapSearchResult($result);
            $tmdbIds[] = $result['id'];
        }

        foreach ($this->movieApi->findByTmdbIds($tmdbIds) as $movie) {
            $searchResults[$movie->getTmdbId()]['ids']['movary'] = $movie->getId();
        }

        return $searchResults;
    }

    private function mapSearchResult(array $tmdbResponse) : array
    {
        return [
            'title' => $tmdbResponse['title'],
            'releaseDate' => $tmdbResponse['release_date'],
            'overview' => $tmdbResponse['overview'],
            'originalLanguage' => $tmdbResponse['original_language'],
            'tmdbPosterPath' => $tmdbResponse['poster_path'],
            'ids' => [
                'movary' => null,
                'tmdb' => $tmdbResponse['id'],
            ],
        ];
    }
}
