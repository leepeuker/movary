<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Movary\Api\Tmdb;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Domain\Movie;
use Movary\Domain\Movie\MovieEntity;
use Movary\Service\UrlGenerator;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;
use Movary\ValueObject\Year;

class MovieHistoryApi
{
    public function __construct(
        private readonly MovieHistoryRepository $repository,
        private readonly Movie\MovieRepository $movieRepository,
        private readonly Tmdb\TmdbApi $tmdbApi,
        private readonly UrlGenerator $urlGenerator,
    ) {
    }

    public function createOrUpdatePlaysForDate(int $movieId, int $userId, Date $watchedAt, int $plays) : void
    {
        $this->repository->createOrUpdatePlaysForDate($movieId, $userId, $watchedAt, $plays);
    }

    public function deleteByTraktId(TraktId $traktId) : void
    {
        $this->repository->deleteByTraktId($traktId);
    }

    public function deleteByUserId(int $userId) : void
    {
        $this->repository->deleteByUserId($userId);
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, Date $watchedAt) : void
    {
        $this->repository->deleteHistoryByIdAndDate($movieId, $userId, $watchedAt);
    }

    public function fetchAveragePersonalRating(int $userId) : float
    {
        return round($this->movieRepository->fetchPersonalRating($userId), 1);
    }

    public function fetchAveragePlaysPerDay(int $userId) : float
    {
        $totalPlayCount = $this->movieRepository->fetchHistoryCount($userId);
        $firstPlayDate = $this->movieRepository->fetchFirstHistoryWatchDate($userId);

        if ($firstPlayDate === null) {
            return 0.0;
        }

        $totalNumberOfDays = $firstPlayDate->getNumberOfDaysSince(Date::create());

        if ($totalNumberOfDays === 0) {
            return $totalPlayCount;
        }

        return round($totalPlayCount / $totalNumberOfDays, 1);
    }

    public function fetchAverageRuntime(int $userId) : int
    {
        return (int)round($this->movieRepository->fetchAverageRuntime($userId));
    }

    public function fetchFirstHistoryWatchDate(int $userId) : ?Date
    {
        return $this->movieRepository->fetchFirstHistoryWatchDate($userId);
    }

    public function fetchHistoryByMovieId(int $movieId, int $userId) : array
    {
        return $this->movieRepository->fetchHistoryByMovieId($movieId, $userId);
    }

    public function fetchHistoryCount(int $userId, ?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchHistoryCount($userId, $searchTerm);
    }

    public function fetchHistoryOrderedByWatchedAtDesc(int $userId) : array
    {
        return $this->movieRepository->fetchHistoryOrderedByWatchedAtDesc($userId);
    }

    public function fetchHistoryPaginated(int $userId, int $limit, int $page, ?string $searchTerm = null) : array
    {
        $historyEntries = $this->movieRepository->fetchHistoryPaginated($userId, $limit, $page, $searchTerm);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($historyEntries);
    }

    public function fetchLastPlays(int $userId) : array
    {
        $lastPlays = $this->movieRepository->fetchLastPlays($userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($lastPlays);
    }

    public function fetchMostWatchedActors(int $userId, int $page = 1, ?int $limit = null, ?Gender $gender = null, ?string $searchTerm = null) : array
    {
        $mostWatchedActors = $this->movieRepository->fetchMostWatchedActors($userId, $page, $limit, $gender, $searchTerm);

        foreach ($mostWatchedActors as $index => $mostWatchedActor) {
            $mostWatchedActors[$index]['gender'] = Gender::createFromInt((int)$mostWatchedActor['gender'])->getAbbreviation();
        }

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($mostWatchedActors);
    }

    public function fetchMostWatchedActorsCount(int $userId, ?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchMostWatchedActorsCount($userId, $searchTerm);
    }

    public function fetchMostWatchedDirectors(int $userId, int $page = 1, ?int $limit = null, ?string $searchTerm = null) : array
    {
        $mostWatchedDirectors = $this->movieRepository->fetchMostWatchedDirectors($userId, $page, $limit, $searchTerm);

        foreach ($mostWatchedDirectors as $index => $mostWatchedDirector) {
            $mostWatchedDirectors[$index]['gender'] = Gender::createFromInt((int)$mostWatchedDirector['gender'])->getAbbreviation();
        }

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($mostWatchedDirectors);
    }

    public function fetchMostWatchedDirectorsCount(int $userId, ?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchMostWatchedDirectorsCount($userId, $searchTerm);
    }

    public function fetchMostWatchedGenres(int $userId) : array
    {
        return $this->movieRepository->fetchMostWatchedGenres($userId);
    }

    public function fetchMostWatchedLanguages(int $userId) : array
    {
        $mostWatchedLanguages = $this->movieRepository->fetchMostWatchedLanguages($userId);

        foreach ($mostWatchedLanguages as $index => $mostWatchedLanguage) {
            $mostWatchedLanguages[$index]['name'] = $this->tmdbApi->getLanguageByCode($mostWatchedLanguage['language']);
            $mostWatchedLanguages[$index]['code'] = $mostWatchedLanguage['language'];
        }

        return $mostWatchedLanguages;
    }

    public function fetchMostWatchedProductionCompanies(int $userId, ?int $limit = null) : array
    {
        $mostWatchedProductionCompanies = $this->movieRepository->fetchMostWatchedProductionCompanies($userId, $limit);

        foreach ($mostWatchedProductionCompanies as $index => $productionCompany) {
            $moviesByProductionCompany = $this->movieRepository->fetchMoviesByProductionCompany($productionCompany['id'], $userId);
            unset($mostWatchedProductionCompanies[$index]['id']);

            foreach ($moviesByProductionCompany as $movieByProductionCompany) {
                $mostWatchedProductionCompanies[$index]['movies'][] = $movieByProductionCompany['title'];
            }
        }

        return $mostWatchedProductionCompanies;
    }

    public function fetchMostWatchedReleaseYears(int $userId) : array
    {
        return $this->movieRepository->fetchMostWatchedReleaseYears($userId);
    }

    public function fetchPlaysForMovieIdOnDate(int $movieId, int $userId, Date $watchedAt) : int
    {
        return $this->movieRepository->fetchPlaysForMovieIdAtDate($movieId, $userId, $watchedAt);
    }

    public function fetchTotalHoursWatched(int $userId) : int
    {
        $minutes = $this->movieRepository->fetchTotalMinutesWatched($userId);

        return (int)round($minutes / 60);
    }

    public function fetchUniqueMovieGenres(int $userId) : array
    {
        return $this->movieRepository->fetchUniqueMovieGenres($userId);
    }

    public function fetchUniqueMovieInHistoryCount(int $userId, ?string $searchTerm = null, ?Year $releaseYear = null, ?string $language = null, ?string $genre = null) : int
    {
        return $this->movieRepository->fetchUniqueMovieInHistoryCount($userId, $searchTerm, $releaseYear, $language, $genre);
    }

    public function fetchUniqueMovieLanguages(int $userId) : array
    {
        $uniqueLanguages = [];

        foreach ($this->movieRepository->fetchUniqueMovieLanguages($userId) as $index => $item) {
            if (empty($item) === true) {
                continue;
            }

            $uniqueLanguages[$index]['name'] = $this->tmdbApi->getLanguageByCode($item);
            $uniqueLanguages[$index]['code'] = $item;
        }

        return $uniqueLanguages;
    }

    public function fetchUniqueMovieReleaseYears(int $userId) : array
    {
        return $this->movieRepository->fetchUniqueMovieReleaseYears($userId);
    }

    public function fetchUniqueMoviesPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm = null,
        string $sortBy = 'title',
        string $sortOrder = 'ASC',
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
    ) : array {
        $movies = $this->movieRepository->fetchUniqueMoviesPaginated(
            $userId,
            $limit,
            $page,
            $searchTerm,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
            $genre,
        );

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function findByTraktId(TraktId $traktId) : ?MovieEntity
    {
        return $this->movieRepository->findByTraktId($traktId);
    }

    public function findHistoryPlaysByMovieIdAndDate(int $movieId, int $userId, Date $watchedAt) : ?int
    {
        return $this->movieRepository->findPlaysForMovieIdAndDate($movieId, $userId, $watchedAt);
    }
}