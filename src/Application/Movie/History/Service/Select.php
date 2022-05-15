<?php declare(strict_types=1);

namespace Movary\Application\Movie\History\Service;

use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;

class Select
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function fetchAverage10Rating() : float
    {
        return round($this->repository->fetchAverage10Rating(), 1);
    }

    public function fetchAveragePlaysPerDay() : float
    {
        $totalPlayCount = $this->repository->fetchHistoryCount();
        $firstPlayDate = $this->repository->fetchFirstHistoryWatchDate();

        if ($firstPlayDate === null) {
            return 0.0;
        }

        $totalNumberOfDays = $firstPlayDate->getNumberOfDaysSince(Date::create());

        return round($totalPlayCount / $totalNumberOfDays, 1);
    }

    public function fetchAverageRuntime() : int
    {
        return (int)round($this->repository->fetchAverageRuntime());
    }

    public function fetchFirstHistoryWatchDate() : ?Date
    {
        return $this->repository->fetchFirstHistoryWatchDate();
    }

    public function fetchHistoryCount(?string $searchTerm = null) : int
    {
        return $this->repository->fetchHistoryCount($searchTerm);
    }

    public function fetchHistoryOrderedByWatchedAtDesc() : array
    {
        return $this->repository->fetchHistoryOrderedByWatchedAtDesc();
    }

    public function fetchHistoryPaginated(int $limit, int $page, ?string $searchTerm = null) : array
    {
        return $this->repository->fetchHistoryPaginated($limit, $page, $searchTerm);
    }

    public function fetchLastPlays() : array
    {
        return $this->repository->fetchLastPlays();
    }

    public function fetchMostWatchedActors(?int $limit = null, ?Gender $gender = null) : array
    {
        $mostWatchedActors = $this->repository->fetchMostWatchedActors($limit, $gender);

        foreach ($mostWatchedActors as $index => $mostWatchedActor) {
            $mostWatchedActors[$index]['gender'] = Gender::createFromInt((int)$mostWatchedActor['gender'])->getAbbreviation();
        }

        return $mostWatchedActors;
    }

    public function fetchMostWatchedGenres() : array
    {
        return $this->repository->fetchMostWatchedGenres();
    }

    public function fetchMostWatchedProductionCompanies() : array
    {
        $mostWatchedProductionCompanies = $this->repository->fetchMostWatchedProductionCompany();

        foreach ($mostWatchedProductionCompanies as $index => $productionCompany) {
            $moviesByProductionCompany = $this->repository->fetchMoviesByProductionCompany($productionCompany['id']);
            unset($mostWatchedProductionCompanies[$index]['id']);

            foreach ($moviesByProductionCompany as $movieByProductionCompany) {
                $mostWatchedProductionCompanies[$index]['movies'][] = $movieByProductionCompany['title'];
            }
        }

        return $mostWatchedProductionCompanies;
    }

    public function fetchMoviesOrderedByMostWatchedDesc() : array
    {
        return $this->repository->fetchMoviesOrderedByMostWatchedDesc();
    }

    public function fetchPlaysForMovieIdOnDate(int $movieId, Date $watchedAt) : int
    {
        return $this->repository->fetchPlaysForMovieIdAtDate($movieId, $watchedAt);
    }

    public function fetchTotalHoursWatched() : int
    {
        $minutes = $this->repository->fetchTotalMinutesWatched();

        return (int)round($minutes / 60);
    }

    public function fetchUniqueMovieInHistoryCount() : int
    {
        return $this->repository->fetchUniqueMovieInHistoryCount();
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        return $this->repository->findByTraktId($traktId);
    }
}
