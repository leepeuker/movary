<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Movary\Api\Jellyfin\JellyfinApi;
use Movary\Api\Tmdb;
use Movary\Domain\Movie;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\UrlGenerator;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;

class MovieHistoryApi
{
    public function __construct(
        private readonly MovieHistoryRepository $repository,
        private readonly Movie\MovieRepository $movieRepository,
        private readonly Tmdb\TmdbApi $tmdbApi,
        private readonly UrlGenerator $urlGenerator,
        private readonly JobQueueApi $jobQueueApi,
        private readonly UserApi $userApi,
        private readonly JellyfinApi $jellyfinApi,
    ) {
    }

    public function create(int $movieId, int $userId, Date $watchedAt, int $plays, ?string $comment = null) : void
    {
        $this->repository->create($movieId, $userId, $watchedAt, $plays, $comment);

        if ($this->userApi->fetchUser($userId)->hasJellyfinSyncEnabled() === false) {
            return;
        }

        $this->jobQueueApi->addJellyfinSyncMovieJob($userId, [$movieId]);
    }

    public function deleteByUserAndMovieId(int $userId, int $movieId) : void
    {
        $this->repository->deleteByUserAndMovieId($userId, $movieId);
    }

    public function deleteByUserId(int $userId) : void
    {
        $this->repository->deleteByUserId($userId);
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, Date $watchedAt) : void
    {
        $this->repository->deleteHistoryByIdAndDate($movieId, $userId, $watchedAt);

        $this->jobQueueApi->addJellyfinSyncMovieJob($userId, [$movieId]);
    }

    public function fetchActors(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm = null,
        string $sortBy = 'uniqueAppearances',
        ?SortOrder $sortOrder = null,
        ?Gender $gender = null,
    ) : array {
        if ($sortOrder === null) {
            $sortOrder = SortOrder::createDesc();
        }

        $actors = $this->movieRepository->fetchActors(
            $userId,
            $limit,
            $page,
            $searchTerm,
            $sortBy,
            $sortOrder,
            $gender,
        );

        foreach ($actors as $index => $actor) {
            $actors[$index]['gender'] = Gender::createFromInt((int)$actor['gender'])->getAbbreviation();
        }

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($actors);
    }

    public function fetchAveragePersonalRating(int $userId) : float
    {
        return round($this->movieRepository->fetchAveragePersonalRating($userId), 1);
    }

    public function fetchAveragePlaysPerDay(int $userId) : float
    {
        $totalPlayCount = $this->movieRepository->fetchHistoryCount($userId);
        $firstPlayDate = $this->movieRepository->fetchFirstHistoryWatchDate($userId);

        if ($firstPlayDate === null) {
            return 0.0;
        }

        $totalNumberOfDays = $firstPlayDate->getDifferenceInDays(Date::create());

        if ($totalNumberOfDays === 0) {
            return $totalPlayCount;
        }

        return round($totalPlayCount / $totalNumberOfDays, 1);
    }

    public function fetchAverageRuntime(int $userId) : int
    {
        return (int)round($this->movieRepository->fetchAverageRuntime($userId));
    }

    public function fetchDirectors(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm = null,
        string $sortBy = 'uniqueAppearances',
        ?SortOrder $sortOrder = null,
        ?Gender $gender = null,
    ) : array {
        if ($sortOrder === null) {
            $sortOrder = SortOrder::createDesc();
        }

        $directors = $this->movieRepository->fetchDirectors(
            $userId,
            $limit,
            $page,
            $searchTerm,
            $sortBy,
            $sortOrder,
            $gender,
        );

        foreach ($directors as $index => $director) {
            $directors[$index]['gender'] = Gender::createFromInt((int)$director['gender'])->getAbbreviation();
        }

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($directors);
    }

    public function fetchDirectorsCount(int $userId, ?string $searchTerm = null, ?Gender $gender = null) : int
    {
        return $this->movieRepository->fetchDirectorsCount($userId, $searchTerm, $gender);
    }

    public function fetchFirstHistoryWatchDate(int $userId) : ?Date
    {
        return $this->movieRepository->fetchFirstHistoryWatchDate($userId);
    }

    public function fetchHistoryByMovieId(int $movieId, int $userId) : array
    {
        return $this->movieRepository->fetchHistoryByMovieId($movieId, $userId);
    }

    public function fetchHistoryForUserByMovieIds(int $userId, array $movieIds) : array
    {
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

    public function fetchMostWatchedActorsCount(int $userId, ?string $searchTerm = null, ?Gender $gender = null) : int
    {
        return $this->movieRepository->fetchActorsCount($userId, $searchTerm, $gender);
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

    public function fetchMovieIdsWithUserWatchHistory(int $userId, array $movieIds) : array
    {
        return $this->movieRepository->fetchMovieIdsWithUserWatchHistory($userId, $movieIds);
    }

    public function fetchTotalHoursWatched(int $userId) : int
    {
        $minutes = $this->movieRepository->fetchTotalMinutesWatched($userId);

        return (int)round($minutes / 60);
    }

    public function fetchTotalPlaysForMovieAndUserId(int $movieId, int $userId) : int
    {
        return $this->movieRepository->fetchTotalPlaysForMovieAndUserId($movieId, $userId);
    }

    public function fetchUniqueActorGenders(int $userId) : array
    {
        $uniqueActorGenders = $this->movieRepository->fetchUniqueActorGenders($userId);

        $uniqueActorGendersEnriched = [];
        foreach ($uniqueActorGenders as $uniqueActorGender) {
            if ($uniqueActorGender === '0') {
                continue;
            }

            $uniqueActorGendersEnriched[] = [
                'id' => $uniqueActorGender,
                'name' => Gender::createFromInt((int)$uniqueActorGender)->getText()
            ];
        }

        return $uniqueActorGendersEnriched;
    }

    public function fetchUniqueDirectorsGenders(int $userId) : array
    {
        $uniqueDirectorsGenders = $this->movieRepository->fetchUniqueDirectorsGenders($userId);

        $uniqueDirectorsGendersEnriched = [];
        foreach ($uniqueDirectorsGenders as $uniqueDirectorGender) {
            if ($uniqueDirectorGender === '0') {
                continue;
            }

            $uniqueDirectorsGendersEnriched[] = [
                'id' => $uniqueDirectorGender,
                'name' => Gender::createFromInt((int)$uniqueDirectorGender)->getText()
            ];
        }

        return $uniqueDirectorsGendersEnriched;
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

        $languageNames = array_column($uniqueLanguages, 'name');
        array_multisort($languageNames, SORT_ASC, $uniqueLanguages);

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
        ?SortOrder $sortOrder = null,
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
    ) : array {
        if ($sortOrder === null) {
            $sortOrder = SortOrder::createAsc();
        }

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

    public function findHistoryEntryForMovieByUserOnDate(int $movieId, int $userId, Date $watchedAt) : ?MovieHistoryEntity
    {
        return $this->movieRepository->findHistoryEntryForMovieByUserOnDate($movieId, $userId, $watchedAt);
    }

    public function update(int $movieId, int $userId, Date $watchedAt, int $plays, ?string $comment = null) : void
    {
        $this->repository->update($movieId, $userId, $watchedAt, $plays, $comment);
    }

    public function updateHistoryComment(int $movieId, int $userId, Date $watchAt, ?string $comment) : void
    {
        $this->repository->updateHistoryComment($movieId, $userId, $watchAt, $comment);
    }
}
