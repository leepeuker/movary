<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Watchlist;

use Movary\Domain\User\UserApi;
use Movary\Service\UrlGenerator;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;

class MovieWatchlistApi
{
    public function __construct(
        private readonly MovieWatchlistRepository $repository,
        private readonly UrlGenerator $urlGenerator,
        private readonly UserApi $userApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function addMovieToWatchlist(int $userId, int $movieId, ?DateTime $addedAt = null) : void
    {
        if ($this->repository->hasMovieInWatchlist($userId, $movieId) === true) {
            $this->logger->debug('Skip adding movie to watchlist because it already exists', ['userId' => $userId, 'movieId' => $movieId]);

            return;
        }

        $this->logger->debug('Adding movie to watchlist', ['userId' => $userId, 'movieId' => $movieId]);

        $this->repository->addMovieToWatchlist($userId, $movieId, $addedAt);
    }

    public function fetchAllWatchlistItems(int $userId) : ?array
    {
        return $this->repository->fetchAllWatchlistItems($userId);
    }

    public function fetchWatchlistCount(int $userId, ?string $searchTerm = null) : int
    {
        return $this->repository->fetchWatchlistCount($userId, $searchTerm);
    }

    public function fetchWatchlistPaginated(int $userId, int $limit, int $page, ?string $searchTerm = null) : array
    {
        $watchlistEntries = $this->repository->fetchWatchlistPaginated($userId, $limit, $page, $searchTerm);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($watchlistEntries);
    }

    public function hasMovieInWatchlist(int $userId, int $movieId) : bool
    {
        return $this->repository->hasMovieInWatchlist($userId, $movieId);
    }

    public function removeMovieFromWatchlist(int $userId, int $movieId) : void
    {
        $this->repository->removeMovieFromWatchlist($userId, $movieId);
    }

    public function removeMovieFromWatchlistAutomatically(int $movieId, int $userId) : void
    {
        if ($this->userApi->fetchUser($userId)->hasWatchlistAutomaticRemovalEnabled() === false) {
            return;
        }

        $this->removeMovieFromWatchlist($userId, $movieId);
    }
}
