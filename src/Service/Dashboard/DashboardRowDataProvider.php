<?php declare(strict_types=1);

namespace Movary\Service\Dashboard;

use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Service\Dashboard\Dto\DashboardRow;
use Movary\ValueObject\Gender;
use RuntimeException;

class DashboardRowDataProvider
{
    public function __construct(
        private readonly MovieHistoryApi $movieHistoryApi,
        private readonly MovieWatchlistApi $movieWatchlistApi,
    ) {
    }

    // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function fetch(DashboardRow $row, int $requestedUserId, ?int $viewerUserId) : array
    {
        return match (true) {
            $row->isLastPlays() => ['lastPlays' => $this->movieHistoryApi->fetchLastPlays($requestedUserId)],
            $row->isLastPlaysCinema() => ['lastPlaysCinema' => $this->movieHistoryApi->fetchLastPlaysCinema($requestedUserId)],
            $row->isMostWatchedActors() => ['mostWatchedActors' => $this->movieHistoryApi->fetchActors($requestedUserId, 6, 1, gender: Gender::createMale(), personFilterUserId: $viewerUserId)],
            $row->isMostWatchedActresses() => ['mostWatchedActresses' => $this->movieHistoryApi->fetchActors($requestedUserId, 6, 1, gender: Gender::createFemale(), personFilterUserId: $viewerUserId)],
            $row->isMostWatchedDirectors() => ['mostWatchedDirectors' => $this->movieHistoryApi->fetchDirectors($requestedUserId, 6, 1, personFilterUserId: $viewerUserId)],
            $row->isMostWatchedLanguages() => ['mostWatchedLanguages' => $this->movieHistoryApi->fetchMostWatchedLanguages($requestedUserId)],
            $row->isMostWatchedGenres() => ['mostWatchedGenres' => $this->movieHistoryApi->fetchMostWatchedGenres($requestedUserId)],
            $row->isMostWatchedProductionCompanies() => ['mostWatchedProductionCompanies' => $this->movieHistoryApi->fetchMostWatchedProductionCompanies($requestedUserId, 12)],
            $row->isMostWatchedReleaseYears() => ['mostWatchedReleaseYears' => $this->movieHistoryApi->fetchMostWatchedReleaseYears($requestedUserId)],
            $row->isWatchlist() => ['watchlistItems' => $this->movieWatchlistApi->fetchWatchlistPaginated($requestedUserId, 6, 1)],
            $row->isTopLocations() => ['topLocations' => $this->movieHistoryApi->fetchTopLocations($requestedUserId)],
            $row->isMostWatchedProductionCountries() => ['mostWatchedProductionCountries' => $this->movieHistoryApi->fetchMostWatchedProductionCountries($requestedUserId)],
            $row->isPersonalRatingDistribution() => ['personalRatingDistribution' => $this->movieHistoryApi->fetchPersonalRatingDistribution($requestedUserId)],
            default => throw new RuntimeException('Unsupported dashboard row: ' . $row->getId()),
        };
    }
}
