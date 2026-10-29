<?php declare(strict_types=1);

namespace Movary\Domain\Movie\History;

use Doctrine\DBAL\Connection;
use Movary\Api\Tmdb;
use Movary\Domain\Movie;
use Movary\Domain\User\UserApi;
use Movary\JobQueue\JobQueueApi;
use Movary\Service\ImageUrlService;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;
use Movary\ValueObject\MovieTitleDisplay;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;

class MovieHistoryApi
{
    public function __construct(
        private readonly MovieHistoryRepository $repository,
        private readonly Movie\MovieRepository $movieRepository,
        private readonly Tmdb\TmdbApi $tmdbApi,
        private readonly ImageUrlService $urlGenerator,
        private readonly JobQueueApi $jobQueueApi,
        private readonly UserApi $userApi,
        private readonly Connection $dbConnection,
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
        ?bool $postToMastodon = null,
    ) : array {
        if ($position === null) {
            $position = (int)$this->findHighestPositionForWatchDate($userId, $watchedAt) + 1;
        }

        $playIds = $this->dbConnection->transactional(function () use ($movieId, $userId, $watchedAt, $plays, $comment, $position, $locationId) : array {
            $created = [];
            for ($index = 0; $index < $plays; $index++) {
                $created[] = $this->repository->createPlay(
                    $movieId,
                    $userId,
                    $watchedAt,
                    $index === 0 ? $comment : null,
                    min($position + $index, 32767),
                    $index === 0 ? $locationId : null,
                );
            }

            return $created;
        });

        $user = $this->userApi->fetchUser($userId);
        if ($user->hasJellyfinSyncEnabled() === true) {
            $this->jobQueueApi->addJellyfinExportMoviesJob($userId, [$movieId]);
        }

        if ($user->isMastodonEnabled() === true && $postToMastodon === true) {
            foreach ($playIds as $playId) {
                $this->jobQueueApi->addMastodonPostPlayJob($userId, $movieId, $watchedAt, $playId);
            }
        }

        return $playIds;
    }

    public function createPlay(
        int $movieId,
        int $userId,
        ?Date $watchedAt,
        ?int $position = null,
        ?string $comment = null,
        ?int $locationId = null,
        ?bool $postToMastodon = null,
    ) : int {
        return $this->create($movieId, $userId, $watchedAt, 1, $position, $comment, $locationId, $postToMastodon)[0];
    }

    public function findPlayById(int $playId, int $userId) : ?MovieHistoryEntity
    {
        return $this->repository->findPlayById($playId, $userId);
    }

    public function updatePlayById(
        int $playId,
        int $userId,
        ?Date $watchedAt,
        int $position,
        ?string $comment,
        ?int $locationId,
        ?bool $postToMastodon = null,
    ) : void {
        $play = $this->repository->findPlayById($playId, $userId);
        if ($play === null) {
            return;
        }

        $this->repository->updatePlayById($playId, $userId, $watchedAt, $position, $comment, $locationId);

        $user = $this->userApi->fetchUser($userId);
        if ($user->hasJellyfinSyncEnabled() === true) {
            $this->jobQueueApi->addJellyfinExportMoviesJob($userId, [$play->getMovieId()]);
        }
        if ($user->isMastodonEnabled() === true && $postToMastodon === true) {
            $this->jobQueueApi->addMastodonPostPlayJob($userId, $play->getMovieId(), $watchedAt, $playId);
        }
    }

    public function deletePlayById(int $playId, int $userId) : void
    {
        $play = $this->repository->findPlayById($playId, $userId);
        if ($play === null) {
            return;
        }

        $this->repository->deletePlayById($playId, $userId);

        if ($this->userApi->fetchUser($userId)->hasJellyfinSyncEnabled() === true) {
            $this->jobQueueApi->addJellyfinExportMoviesJob($userId, [$play->getMovieId()]);
        }
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

    /**
     * @return list<array{rating: int, count: int}>
     */
    public function fetchPersonalRatingDistribution(int $userId) : array
    {
        $countsByRating = [];
        foreach ($this->movieRepository->fetchPersonalRatingDistribution($userId) as $rating) {
            $countsByRating[$rating['rating']] = $rating['count'];
        }

        $distribution = [];
        for ($rating = 1; $rating <= 10; ++$rating) {
            $distribution[] = ['rating' => $rating, 'count' => $countsByRating[$rating] ?? 0];
        }

        return $distribution;
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

    public function fetchPlaysByMovieId(int $movieId, int $userId) : array
    {
        return $this->movieRepository->fetchPlaysByMovieId($movieId, $userId);
    }

    public function fetchHistoryCount(int $userId, ?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchHistoryCount($userId, $searchTerm);
    }

    public function fetchLegacyHistoryCount(int $userId, ?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchLegacyHistoryCount($userId, $searchTerm);
    }

    public function fetchHistoryPaginated(int $userId, int $limit, int $page, ?string $searchTerm = null, ?SortOrder $sortOrder = null) : array
    {
        $historyEntries = $this->movieRepository->fetchHistoryPaginated($userId, $limit, $page, $sortOrder ?? SortOrder::createDesc(), $searchTerm);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($historyEntries);
    }

    public function fetchLegacyHistoryPaginated(int $userId, int $limit, int $page, ?string $searchTerm = null, ?SortOrder $sortOrder = null) : array
    {
        $entries = $this->movieRepository->fetchLegacyHistoryPaginated($userId, $limit, $page, $sortOrder ?? SortOrder::createDesc(), $searchTerm);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($entries);
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
        return $this->movieRepository->fetchMostWatchedProductionCompanies($userId, $limit);
    }

    public function fetchMostWatchedProductionCountries(int $userId) : array
    {
        return $this->movieRepository->fetchMostWatchedProductionCountries($userId);
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
        ?string $productionCountryCode = null,
        ?MovieTitleDisplay $movieTitleDisplay = null,
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
            $productionCountryCode,
            $movieTitleDisplay ?? MovieTitleDisplay::createLocalized(),
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

    public function fetchUniqueProductionCountries(int $userId) : array
    {
        return $this->movieRepository->fetchUniqueProductionCountries($userId);
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
        ?string $productionCountryCode = null,
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
            $productionCountryCode,
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
        ?string $productionCountryCode = null,
        ?MovieTitleDisplay $movieTitleDisplay = null,
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
            $productionCountryCode,
            $movieTitleDisplay ?? MovieTitleDisplay::createLocalized(),
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

    public function findHighestPositionForWatchDate(int $userId, ?Date $watchedAt) : ?int
    {
        return $this->repository->fetchHighestPositionForWatchDate($userId, $watchedAt);
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
        ?int $position,
        ?string $comment = null,
        ?int $locationId = null,
        ?bool $postToMastodon = null,
        bool $commentProvided = false,
    ) : bool {
        $createdIds = $this->dbConnection->transactional(
            fn() : array => $this->reconcileDateGroup($movieId, $userId, $watchedAt, $plays, $position, $comment, $locationId, $commentProvided),
        );

        $user = $this->userApi->fetchUser($userId);

        if ($user->isMastodonEnabled() === true && $postToMastodon === true) {
            foreach ($createdIds as $playId) {
                $this->jobQueueApi->addMastodonPostPlayJob($userId, $movieId, $watchedAt, $playId);
            }
        }

        return $createdIds !== [];
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

    private function reconcileDateGroup(
        int $movieId,
        int $userId,
        ?Date $watchedAt,
        int $plays,
        ?int $position,
        ?string $comment,
        ?int $locationId,
        bool $commentProvided,
    ) : array {
        $rows = $this->repository->fetchPlaysByMovieAndDate($movieId, $userId, $watchedAt);
        $existingCount = count($rows);

        if ($plays < $existingCount) {
            $removable = array_filter(
                array_reverse($rows),
                fn(array $row) : bool => $row['comment'] === null
                    && $row['location_id'] === null
                    && $this->repository->hasMastodonJobReference((int)$row['id'], $userId) === false,
            );
            if (count($removable) < $existingCount - $plays) {
                throw new PlayCountConflict('Cannot reduce plays without deleting annotated history.');
            }

            foreach (array_slice(array_values($removable), 0, $existingCount - $plays) as $row) {
                $this->repository->deletePlayById((int)$row['id'], $userId);
            }

            $rows = $this->repository->fetchPlaysByMovieAndDate($movieId, $userId, $watchedAt);
        }

        $createdIds = [];
        $existingCount = count($rows);
        $nextPosition = $position ?? ((int)$this->findHighestPositionForWatchDate($userId, $watchedAt) + 1);
        for ($index = count($rows); $index < $plays; $index++) {
            $createdIds[] = $this->repository->createPlay(
                $movieId,
                $userId,
                $watchedAt,
                $index === 0 ? $comment : null,
                $this->positionForCreatedPlay($position, $nextPosition, $index, $existingCount),
                $index === 0 ? $locationId : null,
            );
        }

        foreach ($rows as $index => $row) {
            $this->repository->updatePlayById(
                (int)$row['id'],
                $userId,
                $watchedAt,
                $this->positionForExistingPlay($position, $row, $index),
                $index === 0 && ($commentProvided || $comment !== null) ? $comment : $row['comment'],
                $index === 0 && $locationId !== null ? $locationId : $row['location_id'],
            );
        }

        return $createdIds;
    }

    private function positionForCreatedPlay(?int $requestedPosition, int $nextPosition, int $index, int $existingCount) : int
    {
        return min($nextPosition + ($requestedPosition === null ? $index - $existingCount : $index), 32767);
    }

    private function positionForExistingPlay(?int $requestedPosition, array $row, int $index) : int
    {
        return $requestedPosition === null ? (int)$row['position'] : min($requestedPosition + $index, 32767);
    }
}
