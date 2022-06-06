<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Tmdb\Dto\Cast;
use Movary\Api\Tmdb\Dto\Crew;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\Application\Movie\Entity;
use Movary\Application\Movie\Repository;
use Movary\Application\Person;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;

class Update
{
    public function __construct(
        private readonly Repository $repository,
        private readonly Movie\Genre\Service\Create $movieGenreCreateService,
        private readonly Movie\Genre\Service\Delete $movieGenreDeleteService,
        private readonly Movie\ProductionCompany\Service\Create $movieProductionCompanyCreateService,
        private readonly Movie\ProductionCompany\Service\Delete $movieProductionCompanyDeleteService,
        private readonly Person\Api $personApi,
        private readonly Movie\Cast\Service\Create $movieCastCreateService,
        private readonly Movie\Cast\Service\Delete $movieCasteDeleteService,
        private readonly Movie\Crew\Service\Create $movieCrewCreateService,
        private readonly Movie\Crew\Service\Delete $movieCrewDeleteService,
    ) {
    }

    public function updateCast(int $movieId, Cast $tmdbCast) : void
    {
        $this->movieCasteDeleteService->deleteByMovieId($movieId);

        foreach ($tmdbCast as $position => $castMember) {
            $person = $this->createOrUpdatePersonByTmdbId(
                $castMember->getPerson()->getTmdbId(),
                $castMember->getPerson()->getName(),
                $castMember->getPerson()->getGender(),
                $castMember->getPerson()->getKnownForDepartment(),
                $castMember->getPerson()->getPosterPath(),
            );

            $this->movieCastCreateService->create($movieId, $person->getId(), $castMember->getCharacter(), $position);
        }
    }

    public function updateCrew(int $movieId, Crew $tmdbCrew) : void
    {
        $this->movieCrewDeleteService->deleteByMovieId($movieId);

        foreach ($tmdbCrew as $position => $crewMember) {
            $person = $this->createOrUpdatePersonByTmdbId(
                $crewMember->getPerson()->getTmdbId(),
                $crewMember->getPerson()->getName(),
                $crewMember->getPerson()->getGender(),
                $crewMember->getPerson()->getKnownForDepartment(),
                $crewMember->getPerson()->getPosterPath(),
            );

            $this->movieCrewCreateService->create($movieId, $person->getId(), $crewMember->getJob(), $crewMember->getDepartment(), $position);
        }
    }

    public function updateDetails(
        int $id,
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
        return $this->repository->updateDetails($id, $tagline, $overview, $originalLanguage, $releaseDate, $runtime, $tmdbVoteAverage, $tmdbVoteCount, $tmdbPosterPath, $imdbId);
    }

    public function updateGenres(int $movieId, Genre\EntityList $genres) : void
    {
        $this->movieGenreDeleteService->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieGenreCreateService->create($movieId, $genre->getId(), (int)$position);
        }
    }

    public function updateLetterboxdId(int $movieId, string $letterboxdId) : void
    {
        $this->repository->updateLetterboxdId($movieId, $letterboxdId);
    }

    public function updateProductionCompanies(int $movieId, Company\EntityList $genres) : void
    {
        $this->movieProductionCompanyDeleteService->deleteByMovieId($movieId);

        foreach ($genres as $position => $genre) {
            $this->movieProductionCompanyCreateService->create($movieId, $genre->getId(), (int)$position);
        }
    }

    public function updateRating10(int $id, ?int $rating10) : void
    {
        $this->repository->updateRating10($id, $rating10);
    }

    public function updateRating5(int $id, ?int $rating5) : void
    {
        $this->repository->updateRating5($id, $rating5);
    }

    public function updateTraktId(int $movieId, TraktId $traktId) : void
    {
        $this->repository->updateTraktId($movieId, $traktId);
    }

    private function createOrUpdatePersonByTmdbId(int $tmdbId, string $name, Gender $gender, ?string $knownForDepartment, ?string $posterPath) : Person\Entity
    {
        $person = $this->personApi->findByTmdbId($tmdbId);

        if ($person === null) {
            $person = $this->personApi->create(
                $name,
                $gender,
                $knownForDepartment,
                $tmdbId,
                $posterPath,
            );
        } elseif ($person->getName() !== $name ||
                  $person->getGender() !== $gender ||
                  $person->getKnownForDepartment() !== $knownForDepartment ||
                  $person->getTmdbPosterPath() !== $posterPath
        ) {
            $this->personApi->update($person->getId(), $name, $gender, $knownForDepartment, $tmdbId, $posterPath);
        }

        return $person;
    }
}
