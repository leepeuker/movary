<?php declare(strict_types=1);

namespace Movary\HttpController\Api\ResponseMapper;

use Movary\HttpController\Api\Dto\DashboardRowDto;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\ValueObject\Date;
use RuntimeException;

class DashboardRowResponseMapper
{
    public function __construct(private readonly WatchlistResponseMapper $watchlistResponseMapper)
    {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function map(DashboardRow $row, array $data) : DashboardRowDto
    {
        $items = match ($row->getKey()) {
            'last-plays', 'last-plays-cinema' => array_map($this->mapMoviePlay(...), array_values($data)[0]),
            'most-watched-actors', 'most-watched-actresses', 'most-watched-directors' => array_map($this->mapPerson(...), array_values($data)[0]),
            'most-watched-genres', 'most-watched-release-years' => array_map($this->mapNamedCount(...), array_values($data)[0]),
            'most-watched-languages' => array_map($this->mapLanguage(...), array_values($data)[0]),
            'most-watched-production-companies' => array_map($this->mapCompany(...), array_values($data)[0]),
            'most-watched-production-countries' => array_map($this->mapCountry(...), array_values($data)[0]),
            'top-locations' => array_map($this->mapLocation(...), array_values($data)[0]),
            'personal-rating-distribution' => array_map($this->mapRating(...), array_values($data)[0]),
            'latest-watchlist' => $this->watchlistResponseMapper->mapWatchlistEntries(array_values($data)[0]),
            default => throw new RuntimeException('Unsupported dashboard row: ' . $row->getKey()),
        };

        return new DashboardRowDto($row->getKey(), $row->getName(), $items);
    }

    private function mapMoviePlay(array $item) : array
    {
        return [
            'movie' => [
                'id' => (int)$item['id'],
                'title' => $item['title'],
                'originalTitle' => $item['original_title'],
                'releaseDate' => $item['release_date'] === null ? null : Date::createFromString($item['release_date']),
                'posterUrl' => $item['poster_path'],
            ],
            'watchedAt' => Date::createFromString($item['watched_at']),
            'personalRating' => $item['user_rating'] === null ? null : (int)$item['user_rating'],
        ];
    }

    private function mapPerson(array $item) : array
    {
        return [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'profileUrl' => $item['poster_path'],
            'uniqueMovieCount' => (int)$item['uniqueCount'],
        ];
    }

    private function mapNamedCount(array $item) : array
    {
        return ['name' => (string)$item['name'], 'count' => (int)$item['count']];
    }

    private function mapLanguage(array $item) : array
    {
        return $this->mapNamedCount($item) + ['code' => $item['code']];
    }

    private function mapCompany(array $item) : array
    {
        return $this->mapNamedCount($item) + ['id' => (int)$item['id']];
    }

    private function mapCountry(array $item) : array
    {
        return ['name' => $item['name'], 'code' => $item['id'], 'count' => (int)$item['count_movies']];
    }

    private function mapLocation(array $item) : array
    {
        return ['id' => (int)$item['id'], 'name' => $item['name'], 'count' => (int)$item['count_plays']];
    }

    private function mapRating(array $item) : array
    {
        return ['rating' => (int)$item['rating'], 'count' => (int)$item['count']];
    }
}
