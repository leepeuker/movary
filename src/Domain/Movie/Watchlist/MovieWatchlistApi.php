<?php declare(strict_types=1);

namespace Movary\Domain\Movie\Watchlist;

use Movary\Api\Tmdb\TmdbApi;
use Movary\Domain\User\UserApi;
use Movary\Service\ImageUrlService;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use Psr\Log\LoggerInterface;

class MovieWatchlistApi
{
    public function __construct(
        private readonly MovieWatchlistRepository $repository,
        private readonly ImageUrlService $urlGenerator,
        private readonly UserApi $userApi,
        private readonly TmdbApi $tmdbApi,
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

    public function fetchAllWatchlistItems(int $userId) : array
    {
        return $this->repository->fetchAllWatchlistItems($userId);
    }

    public function fetchWatchlistItem(int $userId, int $movieId) : array
    {
        return $this->repository->fetchWatchlistItem($userId, $movieId);
    }

    public function fetchUniqueMovieGenres(int $userId) : array
    {
        return $this->repository->fetchUniqueMovieGenres($userId);
    }

    public function fetchUniqueMovieLanguages(int $userId) : array
    {
        $uniqueLanguages = [];

        foreach ($this->repository->fetchUniqueMovieLanguages($userId) as $index => $item) {
            if (empty($item) === true) {
                continue;
            }

            $uniqueLanguages[$index]['name'] = $this->tmdbApi->getLanguageByCode($item);
            $uniqueLanguages[$index]['code'] = $item;
        }

        $languageNames = array_column($uniqueLanguages, 'name');
        array_multisort($languageNames, SORT_ASC, $uniqueLanguages);

        return $uniqueLanguages;
    }

    public function fetchUniqueMovieProductionCountries(int $userId) : array
    {
        return $this->repository->fetchUniqueMovieProductionCountries($userId);
    }

    public function fetchUniqueMovieReleaseYears(int $userId) : array
    {
        return $this->repository->fetchUniqueMovieReleaseYears($userId);
    }

    public function fetchWatchlistCount(
        int $userId,
        ?string $searchTerm = null,
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
        ?string $productionCountry = null,
    ) : int {
        return $this->repository->fetchWatchlistCount($userId, $searchTerm, $releaseYear, $language, $genre, $productionCountry);
    }

    public function fetchWatchlistPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm = null,
        string $sortBy = 'addedAt',
        ?SortOrder $sortOrder = null,
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
        ?string $productionCountryCode = null,
    ) : array {
        if ($sortOrder === null) {
            $sortOrder = SortOrder::createDesc();
        }

        $watchlistEntries = $this->repository->fetchWatchlistPaginated(
            $userId,
            $limit,
            $page,
            $searchTerm,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
            $genre,
            $productionCountryCode,
        );

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
