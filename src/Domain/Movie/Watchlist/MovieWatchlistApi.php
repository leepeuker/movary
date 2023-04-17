<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Watchlist;

use Movary\Api\Tmdb;
use Movary\Domain\Movie;
use Movary\Domain\Movie\History\MovieHistoryRepository;
use Movary\Service\UrlGenerator;

class MovieWatchlistApi
{
    public function __construct(
        private readonly MovieHistoryRepository $repository,
        private readonly Movie\MovieRepository $movieRepository,
    ) {
    }

    public function fetchWatchlistCount(int $userId, ?string $searchTerm = null) : int
    {
        return 0;
    }

    public function fetchWatchlistPaginated(int $userId, int $limit, int $page, ?string $searchTerm = null) : array
    {
        return [];
    }
}
