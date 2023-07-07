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
use Movary\Domain\Movie\History\MovieHistoryApi;
use Movary\Domain\Movie\History\MovieHistoryEntity;
use Movary\Domain\Movie\ProductionCompany\ProductionCompanyApi;
use Movary\Domain\Movie\Watchlist\MovieWatchlistApi;
use Movary\Domain\Person\PersonApi;
use Movary\Service\UrlGenerator;
use Movary\Service\VoteCountFormatter;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\ImdbRating;
use Movary\ValueObject\PersonalRating;
use Movary\ValueObject\SortOrder;
use Movary\ValueObject\Year;
use RuntimeException;

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
        private readonly UrlGenerator $urlGenerator,
        private readonly MovieRepository $repository,
        private readonly ProductionCompanyApi $movieProductionCompanyApi,
        private readonly PersonApi $personApi,
    ) {
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
        );
    }

    public function deleteHistoryByIdAndDate(int $movieId, int $userId, Date $watchedAt) : void
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

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(?int $limit = null, ?array $ids = null) : \Traversable
    {
        return $this->movieRepository->fetchAllOrderedByLastUpdatedAtTmdbAsc($limit, $ids);
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

    public function fetchHistoryByMovieId(int $movieId, int $userId) : array
    {
        return $this->historyApi->fetchHistoryByMovieId($movieId, $userId);
    }

    public function fetchHistoryCount(int $userId) : int
    {
        return $this->historyApi->fetchHistoryCount($userId);
    }

    public function fetchHistoryCountUnique(int $userId) : int
    {
        return $this->historyApi->fetchUniqueMovieInHistoryCount($userId);
    }

    public function fetchHistoryMovieTotalPlays(int $movieId, int $userId) : int
    {
        return $this->historyApi->fetchTotalPlaysForMovieAndUserId($movieId, $userId);
    }

    public function fetchHistoryOrderedByWatchedAtDesc(int $userId) : array
    {
        return $this->historyApi->fetchHistoryOrderedByWatchedAtDesc($userId);
    }

    public function fetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt(?int $maxAgeInHours = null, ?int $limit = null, ?array $filterMovieIds = null) : array
    {
        return $this->movieRepository->fetchMovieIdsHavingImdbIdOrderedByLastImdbUpdatedAt($maxAgeInHours, $limit, $filterMovieIds);
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

    public function fetchUniqueMoviesCount(int $userId, ?string $searchTerm, ?Year $releaseYear, ?string $language, ?string $genre) : int
    {
        return $this->historyApi->fetchUniqueMovieInHistoryCount($userId, $searchTerm, $releaseYear, $language, $genre);
    }

    public function fetchUniqueMoviesPaginated(
        int $userId,
        int $limit,
        int $page,
        ?string $searchTerm,
        string $sortBy,
        SortOrder $sortOrder,
        ?Year $releaseYear,
        ?string $language,
        ?string $genre,
    ) : array {
        return $this->historyApi->fetchUniqueMoviesPaginated(
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

        return [
            'id' => $entity->getId(),
            'tmdbId' => $entity->getTmdbId(),
            'imdbId' => $entity->getImdbId(),
            'title' => $entity->getTitle(),
            'releaseDate' => $entity->getReleaseDate(),
            'posterPath' => $this->urlGenerator->generateImageSrcUrlFromParameters($entity->getTmdbPosterPath(), $entity->getPosterPath()),
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

    public function findHistoryEntryForMovieByUserOnDate(int $id, int $userId, Date $watchedAt) : ?MovieHistoryEntity
    {
        return $this->historyApi->findHistoryEntryForMovieByUserOnDate($id, $userId, $watchedAt);
    }

    public function findUserRating(int $movieId, int $userId) : ?PersonalRating
    {
        return $this->repository->findUserRating($movieId, $userId);
    }

    public function increaseHistoryPlaysForMovieOnDate(int $movieId, int $userId, Date $watchedDate, int $playsToAdd = 1) : void
    {
        $historyEntry = $this->findHistoryEntryForMovieByUserOnDate($movieId, $userId, $watchedDate);

        $this->watchlistApi->removeMovieFromWatchlistAutomatically($movieId, $userId);

        if ($historyEntry === null) {
            $this->historyApi->create(
                $movieId,
                $userId,
                $watchedDate,
                $playsToAdd,
            );

            return;
        }

        $this->historyApi->update(
            $movieId,
            $userId,
            $watchedDate,
            $historyEntry->getPlays() + $playsToAdd,
            $historyEntry->getComment(),
        );
    }

    public function replaceHistoryForMovieByDate(int $movieId, int $userId, Date $watchedAt, int $playsPerDate, ?string $comment = null) : void
    {
        $existingHistoryEntry = $this->findHistoryEntryForMovieByUserOnDate($movieId, $userId, $watchedAt);

        if ($existingHistoryEntry === null) {
            $this->watchlistApi->removeMovieFromWatchlistAutomatically($movieId, $userId);

            $this->historyApi->create(
                $movieId,
                $userId,
                $watchedAt,
                $playsPerDate,
                $comment,
            );

            return;
        }

        if ($existingHistoryEntry->getPlays() < $playsPerDate) {
            $this->watchlistApi->removeMovieFromWatchlistAutomatically($movieId, $userId);
        }

        $this->historyApi->update(
            $movieId,
            $userId,
            $watchedAt,
            $playsPerDate,
            $comment,
        );
    }

    public function updateCast(int $movieId, TmdbCast $tmdbCast) : void
    {
        $this->castApi->deleteByMovieId($movieId);

        foreach ($tmdbCast as $position => $castMember) {
            $person = $this->personApi->createOrUpdatePersonByTmdbId(
                $castMember->getPerson()->getTmdbId(),
                $castMember->getPerson()->getName(),
                $castMember->getPerson()->getGender(),
                $castMember->getPerson()->getKnownForDepartment(),
                $castMember->getPerson()->getPosterPath(),
            );

            $this->castApi->create($movieId, $person->getId(), $castMember->getCharacter(), $position);
        }
    }

    public function updateCrew(int $movieId, TmdbCrew $tmdbCrew) : void
    {
        $this->crewApi->deleteByMovieId($movieId);

        foreach ($tmdbCrew as $position => $crewMember) {
            $person = $this->personApi->createOrUpdatePersonByTmdbId(
                $crewMember->getPerson()->getTmdbId(),
                $crewMember->getPerson()->getName(),
                $crewMember->getPerson()->getGender(),
                $crewMember->getPerson()->getKnownForDepartment(),
                $crewMember->getPerson()->getPosterPath(),
            );

            $this->crewApi->create($movieId, $person->getId(), $crewMember->getJob(), $crewMember->getDepartment(), $position);
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
        );
    }

    public function updateGenres(int $movieId, GenreEntityList $genres) : void
    {
        $this->movieGenreApi->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieGenreApi->create($movieId, $genre->getId(), (int)$position);
        }
    }

    public function updateHistoryComment(int $movieId, int $userId, Date $watchDate, ?string $comment) : void
    {
        $this->historyApi->updateHistoryComment(
            $movieId,
            $userId,
            $watchDate,
            $comment,
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
