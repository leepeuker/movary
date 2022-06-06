<?php declare(strict_types=1);

namespace Movary\Application\Movie\History\Service;

use Matriphe\ISO639\ISO639;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\EntityList;
use Movary\Application\Movie\Repository;
use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;

class Select
{
    public function __construct(
        private readonly Repository $repository,
        private readonly ISO639 $ISO639
    ) {
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

    public function fetchHistoryByMovieId(int $movieId) : array
    {
        return $this->repository->fetchHistoryByMovieId($movieId);
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

    public function fetchHistoryUniqueMovies() : EntityList
    {
        return EntityList::createFromArray($this->repository->fetchHistoryUniqueMovies());
    }

    public function fetchLastPlays() : array
    {
        return $this->repository->fetchLastPlays();
    }

    public function fetchMostWatchedActors(int $page = 1, ?int $limit = null, ?Gender $gender = null, ?string $searchTerm = null) : array
    {
        $mostWatchedActors = $this->repository->fetchMostWatchedActors($page, $limit, $gender, $searchTerm);

        foreach ($mostWatchedActors as $index => $mostWatchedActor) {
            $mostWatchedActors[$index]['gender'] = Gender::createFromInt((int)$mostWatchedActor['gender'])->getAbbreviation();
        }

        return $mostWatchedActors;
    }

    public function fetchMostWatchedActorsCount(?string $searchTerm = null) : int
    {
        return $this->repository->fetchMostWatchedActorsCount($searchTerm);
    }

    public function fetchMostWatchedDirectors(int $page = 1, ?int $limit = null, ?string $searchTerm = null) : array
    {
        $mostWatchedDirectors = $this->repository->fetchMostWatchedDirectors($page, $limit, $searchTerm);

        foreach ($mostWatchedDirectors as $index => $mostWatchedDirector) {
            $mostWatchedDirectors[$index]['gender'] = Gender::createFromInt((int)$mostWatchedDirector['gender'])->getAbbreviation();
        }

        return $mostWatchedDirectors;
    }

    public function fetchMostWatchedDirectorsCount(?string $searchTerm = null) : int
    {
        return $this->repository->fetchMostWatchedDirectorsCount($searchTerm);
    }

    public function fetchMostWatchedGenres() : array
    {
        return $this->repository->fetchMostWatchedGenres();
    }

    public function fetchMostWatchedLanguages() : array
    {
        $mostWatchedLanguages = $this->repository->fetchMostWatchedLanguages();

        foreach ($mostWatchedLanguages as $index => $mostWatchedLanguage) {
            $mostWatchedLanguages[$index]['name'] = $this->ISO639->languageByCode1($mostWatchedLanguage['language']);
        }

        return $mostWatchedLanguages;
    }

    public function fetchMostWatchedProductionCompanies(?int $limit = null) : array
    {
        $mostWatchedProductionCompanies = $this->repository->fetchMostWatchedProductionCompanies($limit);

        foreach ($mostWatchedProductionCompanies as $index => $productionCompany) {
            $moviesByProductionCompany = $this->repository->fetchMoviesByProductionCompany($productionCompany['id']);
            unset($mostWatchedProductionCompanies[$index]['id']);

            foreach ($moviesByProductionCompany as $movieByProductionCompany) {
                $mostWatchedProductionCompanies[$index]['movies'][] = $movieByProductionCompany['title'];
            }
        }

        return $mostWatchedProductionCompanies;
    }

    public function fetchMostWatchedReleaseYears() : array
    {
        return $this->repository->fetchMostWatchedReleaseYears();
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
