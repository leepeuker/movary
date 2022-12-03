<?php declare(strict_types=1);

namespace Movary\Application\Movie;

use Movary\Api\Imdb;
use Movary\Api\Tmdb;
use Movary\Api\Tmdb\Dto\Cast;
use Movary\Api\Tmdb\Dto\Crew;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\Application\Movie\Cast\CastApi;
use Movary\Application\Movie\Crew\CrewApi;
use Movary\Application\Movie\Genre\MovieGenreApi;
use Movary\Application\Movie\History\MovieHistoryApi;
use Movary\Application\Service\UrlGenerator;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\PersonalRating;
use Movary\ValueObject\Year;

class MovieApi
{
    public function __construct(
        private readonly MovieHistoryApi $historyApi,
        private readonly MovieGenreApi $movieGenreApi,
        private readonly CastApi $castApi,
        private readonly CrewApi $crewApi,
        private readonly Tmdb\Api $tmdbApi,
        private readonly MovieRepository $movieRepository,
        private readonly Movie\Service\VoteCountFormatter $voteCountFormatter,
        private readonly Imdb\UrlGenerator $imdbUrlGenerator,
        private readonly Tmdb\TmdbUrlGenerator $tmdbUrlGenerator,
        private readonly UrlGenerator $urlGenerator,
        private readonly MovieRepository $repository
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
            $imdbId
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

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc() : MovieEntityList
    {
        return $this->movieRepository->fetchAllOrderedByLastUpdatedAtTmdbAsc();
    }

    public function fetchByTraktId(TraktId $traktId) : MovieEntity
    {
        $movie = $this->findByTraktId($traktId);

        if ($movie === null) {
            throw new \RuntimeException('Could not find movie with trakt id: ' . $traktId->asInt());
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

    public function updateCast(int $movieId, Cast $tmdbCast) : void
    {
        $this->movieUpdateService->updateCast($movieId, $tmdbCast);
    }

    public function updateCrew(int $movieId, Crew $tmdbCrew) : void
    {
        $this->movieUpdateService->updateCrew($movieId, $tmdbCrew);
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
        return $this->movieUpdateService->updateDetails(
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

    public function updateGenres(int $movieId, Genre\GenreEntityList $genres) : void
    {
        $this->movieUpdateService->updateGenres($movieId, $genres);
    }

    public function updateImdbRating(int $movieId, ?float $imdbRating, ?int $imdbRatingVoteCount) : void
    {
        $this->movieUpdateService->updateImdbRating($movieId, $imdbRating, $imdbRatingVoteCount);
    }

    public function updateLetterboxdId(int $movieId, string $letterboxdId) : void
    {
        $this->movieUpdateService->updateLetterboxdId($movieId, $letterboxdId);
    }

    public function updateProductionCompanies(int $movieId, Company\CompanyEntityList $companies) : void
    {
        $this->movieUpdateService->updateProductionCompanies($movieId, $companies);
    }

    public function updateTraktId(int $movieId, TraktId $traktId) : void
    {
        $this->movieUpdateService->updateTraktId($movieId, $traktId);
    }

    public function updateUserRating(int $movieId, int $userId, ?PersonalRating $rating) : void
    {
        if ($rating === null) {
            $this->movieRepository->deleteUserRating($movieId, $userId);

            return;
        }

        $this->movieUpdateService->setUserRating($movieId, $userId, $rating);
    }
}
