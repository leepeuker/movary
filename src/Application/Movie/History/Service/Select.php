<?php declare(strict_types=1);

namespace Movary\Application\Movie\History\Service;

use Matriphe\ISO639\ISO639;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\EntityList;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;

class Select
{
    public function __construct(
        private readonly Movie\Repository $movieRepository,
        private readonly ISO639 $ISO639
    ) {
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

    public function fetchHistoryByMovieId(int $movieId) : array
    {
        return $this->movieRepository->fetchHistoryByMovieId($movieId);
    }

    public function fetchHistoryCount(int $userId, ?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchHistoryCount($userId, $searchTerm);
    }

    public function fetchHistoryOrderedByWatchedAtDesc() : array
    {
        return $this->movieRepository->fetchHistoryOrderedByWatchedAtDesc();
    }

    public function fetchHistoryPaginated(int $limit, int $page, ?string $searchTerm = null) : array
    {
        return $this->movieRepository->fetchHistoryPaginated($limit, $page, $searchTerm);
    }

    public function fetchHistoryUniqueMovies() : EntityList
    {
        return EntityList::createFromArray($this->movieRepository->fetchHistoryUniqueMovies());
    }

    public function fetchLastPlays(int $userId) : array
    {
        return $this->movieRepository->fetchLastPlays($userId);
    }

    public function fetchMostWatchedActors(int $userId, int $page = 1, ?int $limit = null, ?Gender $gender = null, ?string $searchTerm = null) : array
    {
        $mostWatchedActors = $this->movieRepository->fetchMostWatchedActors($userId, $page, $limit, $gender, $searchTerm);

        foreach ($mostWatchedActors as $index => $mostWatchedActor) {
            $mostWatchedActors[$index]['gender'] = Gender::createFromInt((int)$mostWatchedActor['gender'])->getAbbreviation();
        }

        return $mostWatchedActors;
    }

    public function fetchMostWatchedActorsCount(?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchMostWatchedActorsCount($searchTerm);
    }

    public function fetchMostWatchedDirectors(int $userId, int $page = 1, ?int $limit = null, ?string $searchTerm = null) : array
    {
        $mostWatchedDirectors = $this->movieRepository->fetchMostWatchedDirectors($userId, $page, $limit, $searchTerm);

        foreach ($mostWatchedDirectors as $index => $mostWatchedDirector) {
            $mostWatchedDirectors[$index]['gender'] = Gender::createFromInt((int)$mostWatchedDirector['gender'])->getAbbreviation();
        }

        return $mostWatchedDirectors;
    }

    public function fetchMostWatchedDirectorsCount(?string $searchTerm = null) : int
    {
        return $this->movieRepository->fetchMostWatchedDirectorsCount($searchTerm);
    }

    public function fetchMostWatchedGenres(int $userId) : array
    {
        return $this->movieRepository->fetchMostWatchedGenres($userId);
    }

    public function fetchMostWatchedLanguages(int $userId) : array
    {
        $mostWatchedLanguages = $this->movieRepository->fetchMostWatchedLanguages($userId);

        foreach ($mostWatchedLanguages as $index => $mostWatchedLanguage) {
            $mostWatchedLanguages[$index]['name'] = $this->ISO639->languageByCode1($mostWatchedLanguage['language']);
        }

        return $mostWatchedLanguages;
    }

    public function fetchMostWatchedProductionCompanies(int $userId, ?int $limit = null) : array
    {
        $mostWatchedProductionCompanies = $this->movieRepository->fetchMostWatchedProductionCompanies($userId, $limit);

        foreach ($mostWatchedProductionCompanies as $index => $productionCompany) {
            $moviesByProductionCompany = $this->movieRepository->fetchMoviesByProductionCompany($productionCompany['id']);
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

    public function fetchMoviesOrderedByMostWatchedDesc() : array
    {
        return $this->movieRepository->fetchMoviesOrderedByMostWatchedDesc();
    }

    public function fetchPlaysForMovieIdOnDate(int $movieId, Date $watchedAt) : int
    {
        return $this->movieRepository->fetchPlaysForMovieIdAtDate($movieId, $watchedAt);
    }

    public function fetchTotalHoursWatched(int $userId) : int
    {
        $minutes = $this->movieRepository->fetchTotalMinutesWatched($userId);

        return (int)round($minutes / 60);
    }

    public function fetchUniqueMovieInHistoryCount(int $userId) : int
    {
        return $this->movieRepository->fetchUniqueMovieInHistoryCount($userId);
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        return $this->movieRepository->findByTraktId($traktId);
    }

    public function findHistoryPlaysByMovieIdAndDate(int $movieId, Date $watchedAt) : ?int
    {
        return $this->movieRepository->findPlaysForMovieIdAndDate($movieId, $watchedAt);
    }
}
