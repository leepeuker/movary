<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

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
    ) {
    }

    public function create(
        int $movieId,
        int $userId,
        ?Date $watchedAt,
        int $plays,
        ?int $position = null,
        ?string $comment = null,
        ?int $locationId = null,
    ) : void {
        if ($position === null) {
            $position = $this->findHighestPositionForWatchDate($movieId, $userId, $watchedAt);
        }

        $this->repository->create($movieId, $userId, $watchedAt, $plays, $comment, (int)$position + 1, $locationId);

        if ($this->userApi->fetchUser($userId)->hasJellyfinSyncEnabled() === false) {
            return;
        }

        $this->jobQueueApi->addJellyfinExportMoviesJob($userId, [$movieId]);
    }

    public function deleteByUserAndMovieId(int $userId, int $movieId) : void
    {
        $this->repository->deleteByUserAndMovieId($userId, $movieId);
    }

    public function deleteByUserId(int $userId) : void
    {
        $this->repository->deleteByUserId($userId);
    }

    public function deleteHistoryById(int $movieId, int $userId) : void
    {
        $this->repository->deleteHistoryById($movieId, $userId);

        if ($this->userApi->fetchUser($userId)->hasJellyfinSyncEnabled() === false) {
            return;
        }

        $this->jobQueueApi->addJellyfinExportMoviesJob($userId, [$movieId]);
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, ?Date $watchedAt) : void
    {
        $this->repository->deleteHistoryByIdAndDate($movieId, $userId, $watchedAt);

        if ($this->userApi->fetchUser($userId)->hasJellyfinSyncEnabled() === false) {
            return;
        }

        $this->jobQueueApi->addJellyfinExportMoviesJob($userId, [$movieId]);
    }

    public function fetchActors(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm = null,
        string $sortBy = 'uniqueAppearances',
        ?SortOrder $sortOrder = null,
        ?Gender $gender = null,
        ?int $personFilterUserId = null,
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
            $personFilterUserId,
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
        ?int $personFilterUserId = null,
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
            $personFilterUserId,
        );

        foreach ($directors as $index => $director) {
            $directors[$index]['gender'] = Gender::createFromInt((int)$director['gender'])->getAbbreviation();
        }

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($directors);
    }

    public function fetchDirectorsCount(int $userId, ?string $searchTerm = null, ?Gender $gender = null, ?int $personFilterUserId = null) : int
    {
        return $this->movieRepository->fetchDirectorsCount($userId, $searchTerm, $gender, $personFilterUserId);
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

    public function fetchHistoryPaginated(int $userId, int $limit, int $page, ?string $searchTerm = null, ?SortOrder $sortOrder = null) : array
    {
        $historyEntries = $this->movieRepository->fetchHistoryPaginated($userId, $limit, $page, $sortOrder ?? SortOrder::createDesc(), $searchTerm);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($historyEntries);
    }

    public function fetchLastPlays(int $userId) : array
    {
        $lastPlays = $this->movieRepository->fetchLastPlays($userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($lastPlays);
    }

    public function fetchLastPlaysCinema(int $userId) : array
    {
        $lastPlays = $this->movieRepository->fetchLastPlaysCinema($userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($lastPlays);
    }

    public function fetchMostWatchedActorsCount(int $userId, ?string $searchTerm = null, ?Gender $gender = null, ?int $personFilterUserId = null) : int
    {
        return $this->movieRepository->fetchActorsCount($userId, $searchTerm, $gender, $personFilterUserId);
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

    public function fetchMovieIdsWithWatchDatesByUserId(int $userId) : array
    {
        return $this->movieRepository->fetchMovieIdsWithWatchDatesByUserId($userId);
    }

    public function fetchPlayedMoviesPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm = null,
        string $sortBy = 'title',
        ?SortOrder $sortOrder = null,
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
        ?bool $hasUserRating = null,
        ?int $userRatingMin = null,
        ?int $userRatingMax = null,
        ?int $locationId = null,
    ) : array {
        if ($sortOrder === null) {
            $sortOrder = SortOrder::createAsc();
        }

        $movies = $this->movieRepository->fetchUniqueWatchedMoviesPaginated(
            $userId,
            $limit,
            $page,
            $searchTerm,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
            $genre,
            $hasUserRating,
            $userRatingMin,
            $userRatingMax,
            $locationId,
        );

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function fetchTmdbIdsToLastWatchDatesMap(int $userId, array $tmdbIds) : array
    {
        $map = [];

        foreach ($this->movieRepository->fetchTmdbIdsToLastWatchDatesMap($userId, $tmdbIds) as $row) {
            $map[$row['tmdb_id']] = Date::createFromString($row['latest_watched_at']);
        }

        return $map;
    }

    public function fetchTmdbIdsWithWatchDatesByUserIdAndMovieIds(int $userId, array $movieIds) : array
    {
        return $this->movieRepository->fetchTmdbIdsWithWatchDatesByUserIdAndMovieIds($userId, $movieIds);
    }

    public function fetchTmdbIdsWithoutWatchDateByUserId(int $userId, array $movieIds) : array
    {
        return $this->movieRepository->fetchTmdbIdsWithoutWatchDateByUserId($userId, $movieIds);
    }

    public function fetchTopLocations(int $userId) : array
    {
        return $this->movieRepository->fetchTopLocations($userId);
    }

    public function fetchTotalHoursWatched(int $userId) : int
    {
        $minutes = $this->movieRepository->fetchTotalMinutesWatched($userId);

        return (int)round($minutes / 60);
    }

    public function fetchTotalPlayCount(int $userId) : int
    {
        return $this->movieRepository->fetchTotalPlayCount($userId);
    }

    public function fetchTotalPlayCountUnique(int $userId) : int
    {
        return $this->movieRepository->fetchTotalPlayCountUnique($userId);
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

    public function fetchUniqueLocations(int $userId) : array
    {
        return $this->movieRepository->fetchUniqueLocations($userId);
    }

    public function fetchUniqueMovieGenres(int $userId) : array
    {
        return $this->movieRepository->fetchUniqueMovieGenres($userId);
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

    public function fetchUniqueWatchedMoviesCount(
        int $userId,
        ?string $searchTerm = null,
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
        ?bool $hasUserRating = null,
        ?int $userRatingMin = null,
        ?int $userRatingMax = null,
        ?int $locationId = null,
    ) : int {
        return $this->movieRepository->fetchUniqueWatchedMoviesCount(
            $userId,
            $searchTerm,
            $releaseYear,
            $language,
            $genre,
            $hasUserRating,
            $userRatingMin,
            $userRatingMax,
            $locationId,
        );
    }

    public function fetchUniqueWatchedMoviesPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm = null,
        string $sortBy = 'title',
        ?SortOrder $sortOrder = null,
        ?Year $releaseYear = null,
        ?string $language = null,
        ?string $genre = null,
        ?bool $hasUserRating = null,
        ?int $userRatingMin = null,
        ?int $userRatingMax = null,
        ?int $locationId = null,
    ) : array {
        if ($sortOrder === null) {
            $sortOrder = SortOrder::createAsc();
        }

        $movies = $this->movieRepository->fetchUniqueWatchedMoviesPaginated(
            $userId,
            $limit,
            $page,
            $searchTerm,
            $sortBy,
            $sortOrder,
            $releaseYear,
            $language,
            $genre,
            $hasUserRating,
            $userRatingMin,
            $userRatingMax,
            $locationId,
        );

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function fetchWatchDatesForMovieIds(int $userId, array $movieIds) : array
    {
        $watchDates = [];

        foreach ($this->movieRepository->fetchWatchDatesForMovieIds($userId, $movieIds) as $watchDateData) {
            $watchDates[$watchDateData['movie_id']][$watchDateData['watched_at']] = [
                'plays' => $watchDateData['plays'],
                'comment' => $watchDateData['comment'],
            ];
        }

        return $watchDates;
    }

    public function fetchWatchDatesOrderedByWatchedAtDesc(int $userId) : array
    {
        return $this->movieRepository->fetchWatchDatesOrderedByWatchedAtDesc($userId);
    }

    public function findHighestPositionForWatchDate(int $movieIdToIgnore, int $userId, ?Date $watchedAt) : ?int
    {
        return $this->repository->fetchHighestPositionForWatchDate($movieIdToIgnore, $userId, $watchedAt);
    }

    public function findHistoryEntryForMovieByUserOnDate(int $movieId, int $userId, ?Date $watchedAt) : ?MovieHistoryEntity
    {
        return $this->movieRepository->findHistoryEntryForMovieByUserOnDate($movieId, $userId, $watchedAt);
    }

    public function update(
        int $movieId,
        int $userId,
        ?Date $watchedAt,
        int $plays,
        int $position,
        ?string $comment = null,
        ?int $locationId = null,
    ) : void {
        $this->repository->update($movieId, $userId, $watchedAt, $plays, $position, $comment, $locationId);
    }

    public function updateHistoryComment(
        int $movieId,
        int $userId,
        ?Date $watchAt,
        ?string $comment,
    ) : void {
        $this->repository->updateHistoryComment($movieId, $userId, $watchAt, $comment);
    }

    public function updateHistoryLocation(
        int $movieId,
        int $userId,
        ?Date $watchAt,
        ?int $locationId,
    ) : void {
        $this->repository->updateHistoryLocation($movieId, $userId, $watchAt, $locationId);
    }
}
