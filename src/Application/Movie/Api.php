<?php declare(strict_types=1);

namespace Movary\Application\Movie;

use Movary\Api\Tmdb;
use Movary\Api\Tmdb\Dto\Cast;
use Movary\Api\Tmdb\Dto\Crew;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\PersonalRating;

class Api
{
    public function __construct(
        private readonly Service\Create $movieCreateService,
        private readonly Service\Select $movieSelectService,
        private readonly Service\Update $movieUpdateService,
        private readonly Movie\History\Service\Create $historyCreateService,
        private readonly Movie\History\Service\Delete $historyDeleteService,
        private readonly Movie\History\Service\Select $historySelectService,
        private readonly Movie\Genre\Service\Select $genreSelectService,
        private readonly Movie\Cast\Service\Select $castSelectService,
        private readonly Movie\Crew\Service\Select $crewSelectService,
        private readonly Tmdb\Api $tmdbApi,
        private readonly Repository $movieRepository
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
    ) : Entity {
        return $this->movieCreateService->create(
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
        $currentPlays = $this->historySelectService->findHistoryPlaysByMovieIdAndDate($id, $userId, $watchedAt);

        if ($currentPlays === null) {
            return;
        }

        if ($currentPlays <= $playsToDelete || $playsToDelete === null) {
            $this->historyDeleteService->deleteHistoryByIdAndDate($id, $userId, $watchedAt);

            return;
        }

        $this->historyCreateService->createOrUpdatePlaysForDate($id, $userId, $watchedAt, $currentPlays - $playsToDelete);
    }

    public function deleteHistoryByTraktId(TraktId $traktId) : void
    {
        $this->historyDeleteService->deleteByTraktId($traktId);
    }

    public function deleteHistoryByUserId(int $userId) : void
    {
        $this->historyDeleteService->deleteByUserId($userId);
    }

    public function deleteRatingsByUserId(int $userId) : void
    {
        $this->movieRepository->deleteAllUserRatings($userId);
    }

    public function fetchAll() : EntityList
    {
        return $this->movieSelectService->fetchAll();
    }

    public function fetchAllOrderedByLastUpdatedAtTmdbAsc() : EntityList
    {
        return $this->movieSelectService->fetchAllOrderedByLastUpdatedAtTmdbAsc();
    }

    public function fetchByTraktId(TraktId $traktId) : Entity
    {
        $movie = $this->findByTraktId($traktId);

        if ($movie === null) {
            throw new \RuntimeException('Could not find movie with trakt id: ' . $traktId->asInt());
        }

        return $movie;
    }

    public function fetchHistoryByMovieId(int $movieId, int $userId) : array
    {
        return $this->historySelectService->fetchHistoryByMovieId($movieId, $userId);
    }

    public function fetchHistoryCount(int $userId) : int
    {
        return $this->historySelectService->fetchHistoryCount($userId);
    }

    public function fetchHistoryCountUnique(int $userId) : int
    {
        return $this->historySelectService->fetchUniqueMovieInHistoryCount($userId);
    }

    public function fetchHistoryMoviePlaysOnDate(int $id, int $userId, Date $watchedAt) : int
    {
        return $this->historySelectService->fetchPlaysForMovieIdOnDate($id, $userId, $watchedAt);
    }

    public function fetchHistoryOrderedByWatchedAtDesc(int $userId) : array
    {
        return $this->historySelectService->fetchHistoryOrderedByWatchedAtDesc($userId);
    }

    public function fetchWithActor(int $personId, int $userId) : array
    {
        return $this->movieSelectService->fetchWithActor($personId, $userId);
    }

    public function fetchWithDirector(int $personId, int $userId) : array
    {
        return $this->movieSelectService->fetchWithDirector($personId, $userId);
    }

    public function findById(int $movieId) : ?array
    {
        $entity = $this->movieSelectService->findById($movieId);

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

        return [
            'id' => $entity->getId(),
            'title' => $entity->getTitle(),
            'releaseDate' => $entity->getReleaseDate(),
            'tmdbPosterPath' => $entity->getTmdbPosterPath(),
            'tagline' => $entity->getTagline(),
            'overview' => $entity->getOverview(),
            'runtime' => $renderedRuntime,
            'originalLanguage' => $originalLanguageCode === null ? null : $this->tmdbApi->getLanguageByCode($originalLanguageCode),
        ];
    }

    public function findByLetterboxdId(string $letterboxdId) : ?Entity
    {
        return $this->movieSelectService->findByLetterboxdId($letterboxdId);
    }

    public function findByTmdbId(int $tmdbId) : ?Entity
    {
        return $this->movieSelectService->findByTmdbId($tmdbId);
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        return $this->movieSelectService->findByTraktId($traktId);
    }

    public function findCastByMovieId(int $movieId) : ?array
    {
        return $this->castSelectService->findByMovieId($movieId);
    }

    public function findDirectorsByMovieId(int $movieId) : ?array
    {
        return $this->crewSelectService->findDirectorsByMovieId($movieId);
    }

    public function findGenresByMovieId(int $movieId) : ?array
    {
        return $this->genreSelectService->findByMovieId($movieId);
    }

    public function findUserRating(int $movieId, int $userId) : ?PersonalRating
    {
        return $this->movieSelectService->findUserRating($movieId, $userId);
    }

    public function increaseHistoryPlaysForMovieOnDate(int $movieId, int $userId, Date $watchedAt, int $playsToAdd = 1) : void
    {
        $playsPerDate = $this->fetchHistoryMoviePlaysOnDate($movieId, $userId, $watchedAt);

        $this->historyCreateService->createOrUpdatePlaysForDate($movieId, $userId, $watchedAt, $playsPerDate + $playsToAdd);
    }

    public function replaceHistoryForMovieByDate(int $movieId, int $userId, Date $watchedAt, int $playsPerDate) : void
    {
        $this->historyCreateService->createOrUpdatePlaysForDate($movieId, $userId, $watchedAt, $playsPerDate);
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
    ) : Entity {
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
            $imdbId
        );
    }

    public function updateGenres(int $movieId, Genre\EntityList $genres) : void
    {
        $this->movieUpdateService->updateGenres($movieId, $genres);
    }

    public function updateLetterboxdId(int $movieId, string $letterboxdId) : void
    {
        $this->movieUpdateService->updateLetterboxdId($movieId, $letterboxdId);
    }

    public function updateProductionCompanies(int $movieId, Company\EntityList $companies) : void
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
