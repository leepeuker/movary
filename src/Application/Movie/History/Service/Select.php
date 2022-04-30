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

    public function fetchFirstHistoryWatchDate() : Date
    {
        return $this->repository->fetchFirstHistoryWatchDate();
    }

    public function fetchHistoryCount() : int
    {
        return $this->repository->fetchHistoryCount();
    }

    public function fetchHistoryOrderedByWatchedAtDesc() : array
    {
        return $this->repository->fetchHistoryOrderedByWatchedAtDesc();
    }

    public function fetchMostWatchedActors() : array
    {
        $mostWatchedActors = $this->repository->fetchMostWatchedActors();

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

    public function fetchUniqueMovieInHistoryCount() : int
    {
        return $this->repository->fetchUniqueMovieInHistoryCount();
    }

    public function findByTraktId(TraktId $traktId) : ?Entity
    {
        return $this->repository->findByTraktId($traktId);
    }
}
