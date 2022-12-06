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
use Movary\Domain\Movie\ProductionCompany\ProductionCompanyApi;
use Movary\Domain\Person\PersonApi;
use Movary\Service\UrlGenerator;
use Movary\Service\VoteCountFormatter;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\PersonalRating;
use Movary\ValueObject\Year;
use RuntimeException;

class MovieApi
{
    public function __construct(
        private readonly MovieHistoryApi $historyApi,
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
            $traktId,
            $imdbId,
        );
    }

    public function deleteHistoryByIdAndDate(int $id, int $userId, Date $watchedAt, ?int $playsToDelete = null) : void
    {
        $currentPlays = $this->historyApi->findHistoryPlaysByMovieIdAndDate($id, $userId, $watchedAt);

        if ($currentPlays === null) {
            return;
        }

        if ($currentPlays <= $playsToDelete || $playsToDelete === null) {
            $this->historyApi->deleteHistoryByIdAndDate($id, $userId, $watchedAt);

            return;
        }

        $this->historyApi->createOrUpdatePlaysForDate($id, $userId, $watchedAt, $currentPlays - $playsToDelete);
    }

    public function deleteHistoryByTraktId(TraktId $traktId) : void
    {
        $this->historyApi->deleteByTraktId($traktId);
    }

    public function deleteHistoryByUserId(int $userId) : void
    {
        $this->historyApi->deleteByUserId($userId);
    }

    public function deleteRatingsByUserId(int $userId) : void
    {
        $this->movieRepository->deleteAllUserRatings($userId);
    }

    public function fetchAll() : MovieEntityList
    {
        return $this->repository->fetchAll();
    }

    public function fetchAllOrderedByLastUpdatedAtImdbAsc(?int $maxAgeInHours = null, ?int $limit = null) : MovieEntityList
    {
        return $this->movieRepository->fetchAllOrderedByLastUpdatedAtImdbAsc($maxAgeInHours, $limit);
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc(?int $limit = null) : MovieEntityList
    {
        return $this->movieRepository->fetchAllOrderedByLastUpdatedAtTmdbAsc($limit);
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

    public function fetchHistoryMoviePlaysOnDate(int $id, int $userId, Date $watchedAt) : int
    {
        return $this->historyApi->fetchPlaysForMovieIdOnDate($id, $userId, $watchedAt);
    }

    public function fetchHistoryOrderedByWatchedAtDesc(int $userId) : array
    {
        return $this->historyApi->fetchHistoryOrderedByWatchedAtDesc($userId);
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
        string $sortOrder,
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

    public function findById(int $movieId) : ?array
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
            'title' => $entity->getTitle(),
            'releaseDate' => $entity->getReleaseDate(),
            'posterPath' => $this->urlGenerator->generateImageSrcUrlFromParameters($entity->getTmdbPosterPath(), $entity->getPosterPath()),
            'tagline' => $entity->getTagline(),
            'overview' => $entity->getOverview(),
            'runtime' => $renderedRuntime,
            'imdbUrl' => $imdbId !== null ? $this->imdbUrlGenerator->buildUrl($imdbId) : null,
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

    public function findUserRating(int $movieId, int $userId) : ?PersonalRating
    {
        return $this->repository->findUserRating($movieId, $userId);
    }

    public function increaseHistoryPlaysForMovieOnDate(int $movieId, int $userId, Date $watchedAt, int $playsToAdd = 1) : void
    {
        $playsPerDate = $this->fetchHistoryMoviePlaysOnDate($movieId, $userId, $watchedAt);

        $this->historyApi->createOrUpdatePlaysForDate($movieId, $userId, $watchedAt, $playsPerDate + $playsToAdd);
    }

    public function replaceHistoryForMovieByDate(int $movieId, int $userId, Date $watchedAt, int $playsPerDate) : void
    {
        $this->historyApi->createOrUpdatePlaysForDate($movieId, $userId, $watchedAt, $playsPerDate);
    }

    public function updateCast(int $movieId, TmdbCast $tmdbCast) : void
    {
        $this->castApi->deleteByMovieId($movieId);

        foreach ($tmdbCast as $position => $castMember) {
            $person = $this->personApi->fetchOrCreatePersonByTmdbId(
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
            $person = $this->personApi->fetchOrCreatePersonByTmdbId(
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

    public function updateImdbRating(int $movieId, ?float $imdbRating, ?int $imdbRatingVoteCount) : void
    {
        $this->repository->updateImdbRating($movieId, $imdbRating, $imdbRatingVoteCount);
    }

    public function updateLetterboxdId(int $movieId, string $letterboxdId) : void
    {
        $this->repository->updateLetterboxdId($movieId, $letterboxdId);
    }

    public function updateProductionCompanies(int $movieId, CompanyEntityList $genres) : void
    {
        $this->movieProductionCompanyApi->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieProductionCompanyApi->create($movieId, $genre->getId(), (int)$position);
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

        $this->repository->updateUserRating($movieId, $userId, $rating);
    }
}
