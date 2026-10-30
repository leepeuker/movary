<?php declare(strict_types=1);

namespace Movary\Domain\Movie;

use Movary\Api\Imdb;
use Movary\Api\Tmdb;
use Movary\Api\Tmdb\Dto\TmdbCast;
use Movary\Api\Tmdb\Dto\TmdbCrew;
use Movary\Api\Trakt\ValueObject\TraktId;
use Movary\Domain\Company\CompanyEntityList;
use Movary\Domain\Genre\GenreEntityList;
use Movary\Domain\Movie\Cast\CastApi;
use Movary\Domain\Movie\Crew\CrewApi;
use Movary\Domain\Movie\Genre\MovieGenreApi;
use Movary\Domain\Movie\History\Location\MovieHistoryLocationApi;
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\MovieHistoryEntity;
use Movary\Domain\Movie\ProductionCompany\ProductionCompanyApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\Person\PersonApi;
use Movary\Service\ImageUrlService;
use Movary\Service\VoteCountFormatter;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\ImdbRating;
use Movary\ValueObject\MovieTitleDisplay;
use Movary\ValueObject\PersonalRating;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;
use Traversable;

class MovieApi
{
    public function __construct(
        private readonly MovieHistoryApi $historyApi,
        private readonly MovieWatchlistApi $watchlistApi,
        private readonly MovieGenreApi $movieGenreApi,
        private readonly CastApi $castApi,
        private readonly CrewApi $crewApi,
        private readonly Tmdb\TmdbApi $tmdbApi,
        private readonly MovieRepository $movieRepository,
        private readonly VoteCountFormatter $voteCountFormatter,
        private readonly Imdb\ImdbUrlGenerator $imdbUrlGenerator,
        private readonly Tmdb\TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly ImageUrlService $urlGenerator,
        private readonly MovieRepository $repository,
        private readonly ProductionCompanyApi $movieProductionCompanyApi,
        private readonly PersonApi $personApi,
        private readonly MovieHistoryLocationApi $locationApi,
    ) {
    }

    public function addPlaysForMovieOnDate(
        int $movieId,
        int $userId,
        ?Date $watchedDate,
        int $playsToAdd = 1,
        ?int $position = null,
        ?string $comment = null,
        ?int $locationId = null,
        ?bool $postToMastodon = null,
    ) : void {
        $this->watchlistApi->removeMovieFromWatchlistAutomatically($movieId, $userId);

        $this->historyApi->create(
            $movieId,
            $userId,
            $watchedDate,
            $playsToAdd,
            $position,
            $comment,
            $locationId,
            $postToMastodon,
        );
    }

    public function createPlay(
        int $movieId,
        int $userId,
        ?Date $watchedAt,
        ?int $position = null,
        ?string $comment = null,
        ?int $locationId = null,
    ) : int {
        $this->watchlistApi->removeMovieFromWatchlistAutomatically($movieId, $userId);

        return $this->historyApi->createPlay($movieId, $userId, $watchedAt, $position, $comment, $locationId);
    }

    public function create(
        string $title,
        int $tmdbId,
        ?string $tagline = null,
        ?string $overview = null,
        ?string $originalLanguage = null,
        ?Date $releaseDate = null,
        ?int $runtime = null,
        ?float $tmdbVoteAverage = null,
        ?int $tmdbVoteCount = null,
        ?string $tmdbPosterPath = null,
        ?string $tmdbBackdropPath = null,
        ?TraktId $traktId = null,
        ?string $imdbId = null,
        ?string $originalTitle = null,
    ) : MovieEntity {
        return $this->repository->create(
            $title,
            $tmdbId,
            $tagline,
            $overview,
            $originalLanguage,
            $releaseDate,
            $runtime,
            $tmdbVoteAverage,
            $tmdbVoteCount,
            $tmdbPosterPath,
            $tmdbBackdropPath,
            $traktId,
            $imdbId,
            $originalTitle,
        );
    }

    public function deleteHistoryById(int $movieId, int $userId) : void
    {
        $this->historyApi->deleteHistoryById($movieId, $userId);
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, ?Date $watchedAt) : void
    {
        $this->historyApi->deleteHistoryByIdAndDate($movieId, $userId, $watchedAt);
    }

    public function deleteHistoryByUserId(int $userId) : void
    {
        $this->historyApi->deleteByUserId($userId);
    }

    public function deleteHistoryForUserByTraktId(int $userId, TraktId $traktId) : void
    {
        $movie = $this->findByTraktId($traktId);

        if ($movie === null) {
            return;
        }

        $this->historyApi->deleteByUserAndMovieId($movie->getId(), $userId);
    }

    public function deleteRatingsByUserId(int $userId) : void
    {
        $this->movieRepository->deleteAllUserRatings($userId);
    }

    public function fetchAll() : MovieEntityList
    {
        return $this->repository->fetchAll();
    }

    public function countAll(?string $searchTerm, ?Year $releaseYear) : int
    {
        return $this->repository->countAll($searchTerm, $releaseYear);
    }

    public function fetchAllPaginated(
        int $limit,
        int $offset,
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear,
    ) : MovieEntityList {
        return $this->repository->fetchAllPaginated($limit, $offset, $searchTerm, $sortBy, $sortOrder, $releaseYear);
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(
        ?int $limit = null,
        ?array $ids = null,
        bool $onlyNeverSynced = false,
    ) : Traversable
    {
        return $this->movieRepository->fetchAllOrderedByLastUpdatedAtTmdbAsc($limit, $ids, $onlyNeverSynced);
    }

    public function fetchById(int $movieId) : MovieEntity
    {
        $movie = $this->repository->findById($movieId);

        if ($movie === null) {
            throw new RuntimeException('Could not find movie with id: ' . $movieId);
        }

        return $movie;
    }

    public function fetchByTraktId(TraktId $traktId) : MovieEntity
    {
        $movie = $this->findByTraktId($traktId);

        if ($movie === null) {
            throw new RuntimeException('Could not find movie with trakt id: ' . $traktId->asInt());
        }

        return $movie;
    }

    public function fetchFromWatchlistWithActor(int $personId, int $userId) : array
    {
        $movies = $this->repository->fetchFromWatchlistWithActor($personId, $userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function fetchFromWatchlistWithDirector(int $personId, int $userId) : array
    {
        $movies = $this->repository->fetchFromWatchlistWithDirector($personId, $userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function fetchHistoryByMovieId(int $movieId, int $userId) : array
    {
        return $this->historyApi->fetchHistoryByMovieId($movieId, $userId);
    }

    public function fetchPlaysByMovieId(int $movieId, int $userId) : array
    {
        return $this->historyApi->fetchPlaysByMovieId($movieId, $userId);
    }

    public function fetchHistoryMovieTotalPlays(int $movieId, int $userId) : int
    {
        return $this->historyApi->fetchTotalPlaysForMovieAndUserId($movieId, $userId);
    }

    public function fetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt(
        ?int $maxAgeInHours = null,
        ?int $limit = null,
        ?array $filterMovieIds = null,
        bool $onlyNeverSynced = false,
    ) : array {
        return $this->movieRepository->fetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt($maxAgeInHours, $limit, $filterMovieIds, $onlyNeverSynced);
    }

    public function fetchPlayedMoviesCount(
        int $userId,
        ?string $searchTerm,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?bool $hasUserRating,
        ?int $userRatingMin,
        ?int $userRatingMax,
    ) : int {
        return $this->historyApi->fetchUniqueWatchedMoviesCount(
            $userId,
            $searchTerm,
            $releaseYear,
            $language,
            $genre,
            $hasUserRating,
            $userRatingMin,
            $userRatingMax,
        );
    }

    public function fetchPlayedMoviesPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?bool $hasUserRating,
        ?int $userRatingMin,
        ?int $userRatingMax,
    ) : array {
        return $this->historyApi->fetchPlayedMoviesPaginated(
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
        );
    }

    public function fetchTotalPlayCount(int $userId) : int
    {
        return $this->historyApi->fetchTotalPlayCount($userId);
    }

    public function fetchTotalPlayCountUnique(int $userId) : int
    {
        return $this->historyApi->fetchTotalPlayCountUnique($userId);
    }

    public function fetchUniqueLocations(int $userId) : array
    {
        return $this->historyApi->fetchUniqueLocations($userId);
    }

    public function fetchUniqueMovieGenres(int $userId) : array
    {
        return $this->historyApi->fetchUniqueMovieGenres($userId);
    }

    public function fetchUniqueMovieLanguages(int $userId) : array
    {
        return $this->historyApi->fetchUniqueMovieLanguages($userId);
    }

    public function fetchUniqueMovieReleaseYears(int $userId) : array
    {
        return $this->historyApi->fetchUniqueMovieReleaseYears($userId);
    }

    public function fetchUniqueProductionCountries(int $userId) : array
    {
        return $this->historyApi->fetchUniqueProductionCountries($userId);
    }

    public function fetchUniqueWatchedMoviesCount(
        int $userId,
        ?string $searchTerm,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?bool $hasUserRating,
        ?int $userRatingMin,
        ?int $userRatingMax,
        ?int $locationId,
        ?string $productionCountryCode,
    ) : int {
        return $this->historyApi->fetchUniqueWatchedMoviesCount(
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
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
        ?bool $hasUserRating,
        ?int $userRatingMin,
        ?int $userRatingMax,
        ?int $locationId,
        ?string $productionCountryCode,
        ?MovieTitleDisplay $movieTitleDisplay = null,
    ) : array {
        return $this->historyApi->fetchUniqueWatchedMoviesPaginated(
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
            $movieTitleDisplay,
        );
    }

    public function fetchWatchDatesForMovies(int $userId, array $playedEntries) : array
    {
        $movieIds = [];

        foreach ($playedEntries as $playedEntry) {
            $movieIds[] = $playedEntry['id'];
        }

        return $this->historyApi->fetchWatchDatesForMovieIds($userId, $movieIds);
    }

    public function fetchPlaysForExport(int $userId) : array
    {
        return $this->historyApi->fetchPlaysForExport($userId);
    }

    public function fetchWithActor(int $personId, int $userId) : array
    {
        $movies = $this->repository->fetchWithActor($personId, $userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function fetchWithDirector(int $personId, int $userId) : array
    {
        $movies = $this->repository->fetchWithDirector($personId, $userId);

        return $this->urlGenerator->replacePosterPathWithImageSrcUrl($movies);
    }

    public function findById(int $movieId) : ?MovieEntity
    {
        return $this->repository->findById($movieId);
    }

    public function findByIdFormatted(int $movieId) : ?array
    {
        $entity = $this->repository->findById($movieId);

        if ($entity === null) {
            return null;
        }

        $renderedRuntime = '';
        $hours = floor($entity->getRuntime() / 60);
        if ($hours > 0) {
            $renderedRuntime .= $hours . 'h';
        }
        $minutes = $entity->getRuntime() % 60;
        if ($minutes > 0) {
            $renderedRuntime .= ' ' . $minutes . 'm';
        }

        $originalLanguageCode = $entity->getOriginalLanguage();

        $imdbId = $entity->getImdbId();

        $posterPath = $this->urlGenerator->generateImageSrcUrlFromParameters(
            $entity->getTmdbPosterPathEffective(),
            $entity->getPosterPath(),
            $entity->getTitle(),
        );

        return [
            'id' => $entity->getId(),
            'tmdbId' => $entity->getTmdbId(),
            'imdbId' => $entity->getImdbId(),
            'title' => $entity->getTitle(),
            'originalTitle' => $entity->getOriginalTitle(),
            'releaseDate' => $entity->getReleaseDate(),
            'posterPath' => $posterPath,
            'tagline' => $entity->getTagline(),
            'overview' => $entity->getOverview(),
            'runtime' => $renderedRuntime,
            'imdbUrl' => $imdbId !== null ? $this->imdbUrlGenerator->buildMovieUrl($imdbId) : null,
            'imdbRatingAverage' => $entity->getImdbRatingAverage(),
            'imdbRatingVoteCount' => $this->voteCountFormatter->formatVoteCount($entity->getImdbVoteCount()),
            'tmdbUrl' => (string)$this->tmdbUrlGenerator->generateMovieUrl($entity->getTmdbId()),
            'tmdbRatingAverage' => $entity->getTmdbVoteAverage(),
            'tmdbRatingVoteCount' => $this->voteCountFormatter->formatVoteCount($entity->getTmdbVoteCount()),
            'originalLanguage' => $originalLanguageCode === null ? null : $this->tmdbApi->getLanguageByCode($originalLanguageCode),
        ];
    }

    public function findByLetterboxdId(string $letterboxdId) : ?MovieEntity
    {
        return $this->repository->findByLetterboxdId($letterboxdId);
    }

    public function findByTitleAndYear(string $title, Year $releaseYear) : ?MovieEntity
    {
        return $this->repository->findByTitleAndYear($title, $releaseYear);
    }

    public function findByTmdbId(int $tmdbId) : ?MovieEntity
    {
        return $this->repository->findByTmdbId($tmdbId);
    }

    public function findByTmdbIds(array $tmdbIds) : array
    {
        $tmdbIdToMovieMap = [];

        foreach ($this->repository->findByTmdbIds($tmdbIds) as $movieData) {
            $movie = MovieEntity::createFromArray($movieData);
            $tmdbIdToMovieMap[$movie->getTmdbId()] = $movie;
        }

        return $tmdbIdToMovieMap;
    }

    public function findByTraktId(TraktId $traktId) : ?MovieEntity
    {
        return $this->repository->findByTraktId($traktId);
    }

    public function findCastByMovieId(int $movieId) : ?array
    {
        return $this->castApi->findByMovieId($movieId);
    }

    public function findDirectorsByMovieId(int $movieId) : ?array
    {
        return $this->crewApi->findDirectorsByMovieId($movieId);
    }

    public function findGenresByMovieId(int $movieId) : ?array
    {
        return $this->movieGenreApi->findByMovieId($movieId);
    }

    public function findHistoryEntryForMovieByUserOnDate(int $id, int $userId, ?Date $watchedAt) : ?MovieHistoryEntity
    {
        return $this->historyApi->findHistoryEntryForMovieByUserOnDate($id, $userId, $watchedAt);
    }

    public function findProductionCountriesByMovieId(int $movieId) : ?array
    {
        return $this->movieRepository->findProductionCountriesByMovieId($movieId);
    }

    public function findUserRating(int $movieId, int $userId) : ?PersonalRating
    {
        return $this->repository->findUserRating($movieId, $userId);
    }

    public function replaceHistoryForMovieByDate(
        int $movieId,
        int $userId,
        ?Date $watchedAt,
        int $playsPerDate,
        ?int $position = null,
        ?string $comment = null,
        ?int $locationId = null,
        ?bool $postToMastodon = null,
        bool $commentProvided = false,
    ) : void {
        $createdPlays = $this->historyApi->update(
            $movieId,
            $userId,
            $watchedAt,
            $playsPerDate,
            $position,
            $comment,
            $locationId,
            $postToMastodon,
            $commentProvided,
        );

        if ($createdPlays === true) {
            $this->watchlistApi->removeMovieFromWatchlistAutomatically($movieId, $userId);
        }
    }

    public function updateCast(int $movieId, TmdbCast $tmdbCast) : void
    {
        $this->castApi->deleteByMovieId($movieId);

        foreach ($tmdbCast as $position => $castMember) {
            $person = $this->personApi->createOrUpdatePersonWithTmdbCreditsData(
                $castMember->getPerson()->getTmdbId(),
                $castMember->getPerson()->getName(),
                $castMember->getPerson()->getGender(),
                $castMember->getPerson()->getKnownForDepartment(),
                $castMember->getPerson()->getPosterPath(),
            );

            $this->castApi->create($movieId, $person->getId(), $castMember->getCharacter(), (int)$position);
        }
    }

    public function updateCrew(int $movieId, TmdbCrew $tmdbCrew) : void
    {
        $this->crewApi->deleteByMovieId($movieId);

        foreach ($tmdbCrew as $position => $crewMember) {
            $person = $this->personApi->createOrUpdatePersonWithTmdbCreditsData(
                $crewMember->getPerson()->getTmdbId(),
                $crewMember->getPerson()->getName(),
                $crewMember->getPerson()->getGender(),
                $crewMember->getPerson()->getKnownForDepartment(),
                $crewMember->getPerson()->getPosterPath(),
            );

            $this->crewApi->create($movieId, $person->getId(), $crewMember->getJob(), $crewMember->getDepartment(), (int)$position);
        }
    }

    public function updateDetails(
        int $movieId,
        ?string $tagline,
        ?string $overview,
        ?string $originalLanguage,
        ?DateTime $releaseDate,
        ?int $runtime,
        ?float $tmdbVoteAverage,
        ?int $tmdbVoteCount,
        ?string $tmdbPosterPath,
        ?string $tmdbBackdropPath,
        ?string $imdbId,
        ?string $originalTitle,
    ) : MovieEntity {
        return $this->repository->updateDetails(
            $movieId,
            $tagline,
            $overview,
            $originalLanguage,
            $releaseDate,
            $runtime,
            $tmdbVoteAverage,
            $tmdbVoteCount,
            $tmdbPosterPath,
            $tmdbBackdropPath,
            $imdbId,
            $originalTitle,
        );
    }

    public function updateGenres(int $movieId, GenreEntityList $genres) : void
    {
        $this->movieGenreApi->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieGenreApi->create($movieId, $genre->getId(), (int)$position);
        }
    }

    public function updateHistoryComment(int $movieId, int $userId, ?Date $watchDate, ?string $comment) : void
    {
        $this->historyApi->updateHistoryComment(
            $movieId,
            $userId,
            $watchDate,
            $comment,
        );
    }

    public function updateHistoryLocation(int $movieId, int $userId, ?Date $watchDate, ?int $locationId) : void
    {
        $this->historyApi->updateHistoryLocation(
            $movieId,
            $userId,
            $watchDate,
            $locationId,
        );
    }

    public function updateHistoryLocationByName(int $movieId, int $userId, ?Date $watchDate, string $locationName) : void
    {
        $locationId = $this->locationApi->createOrUpdate($userId, $locationName);

        $this->historyApi->updateHistoryLocation(
            $movieId,
            $userId,
            $watchDate,
            $locationId,
        );
    }

    public function updateImdbRating(int $movieId, ?ImdbRating $imdbRating) : void
    {
        $this->repository->updateImdbRating($movieId, $imdbRating);
    }

    public function updateImdbTimestamp(int $movieId) : void
    {
        $this->repository->updateImdbTimestamp($movieId);
    }

    public function updateLetterboxdId(int $movieId, string $letterboxdId) : void
    {
        $this->repository->updateLetterboxdId($movieId, $letterboxdId);
    }

    public function updateProductionCompanies(int $movieId, CompanyEntityList $productionCompanies) : void
    {
        $this->movieProductionCompanyApi->deleteByMovieId($movieId);

        foreach ($productionCompanies->getUniqueCompanies() as $position => $productionCompany) {
            $this->movieProductionCompanyApi->create($movieId, $productionCompany->getId(), (int)$position);
        }
    }

    public function updateProductionCountries(int $movieId, array $countriesIso31661) : void
    {
        $this->repository->deleteProductionCountries($movieId);
        $this->repository->updateProductionCountries($movieId, $countriesIso31661);
    }

    public function updateTraktId(int $movieId, TraktId $traktId) : void
    {
        $this->repository->updateTraktId($movieId, $traktId);
    }

    public function updateUserRating(int $movieId, int $userId, ?PersonalRating $rating) : void
    {
        if ($rating === null) {
            $this->movieRepository->deleteUserRating($movieId, $userId);

            return;
        }

        $currentRating = $this->repository->findPersonalMovieRating($movieId, $userId);

        if ($currentRating === null) {
            $this->repository->insertUserRating($movieId, $userId, $rating);

            return;
        }

        if ($currentRating->isEqual($rating) === true) {
            return;
        }

        $this->repository->updateUserRating($movieId, $userId, $rating);
    }
}
