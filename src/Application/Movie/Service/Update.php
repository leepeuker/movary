<?php declare(strict_types=1);

namespace Movary\Application\Movie\Service;

use Movary\Api\Tmdb\Dto\Cast;
use Movary\Api\Tmdb\Dto\Crew;
use Movary\Api\Trakt\ValueObject\Movie\TraktId;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie\Cast\CastApi;
use Movary\Application\Movie\Crew\CrewApi;
use Movary\Application\Movie\Genre\MovieGenreApi;
use Movary\Application\Movie\MovieEntity;
use Movary\Application\Movie\MovieRepository;
use Movary\Application\Movie\ProductionCompany\ProductionCompanyApi;
use Movary\Application\Person;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;
use Movary\ValueObject\PersonalRating;

class Update
{
    public function __construct(
        private readonly MovieRepository $repository,
        private readonly MovieGenreApi $movieGenreApi,
        private readonly ProductionCompanyApi $movieProductionCompanyApi,
        private readonly Person\PersonApi $personApi,
        private readonly CastApi $castApi,
        private readonly CrewApi $crewApi,
    ) {
    }

    public function setUserRating(int $id, int $userId, PersonalRating $rating) : void
    {
        $this->repository->updateUserRating($id, $userId, $rating);
    }

    public function updateCast(int $movieId, Cast $tmdbCast) : void
    {
        $this->castApi->deleteByMovieId($movieId);

        foreach ($tmdbCast as $position => $castMember) {
            $person = $this->createOrUpdatePersonByTmdbId(
                $castMember->getPerson()->getTmdbId(),
                $castMember->getPerson()->getName(),
                $castMember->getPerson()->getGender(),
                $castMember->getPerson()->getKnownForDepartment(),
                $castMember->getPerson()->getPosterPath(),
            );

            $this->castApi->create($movieId, $person->getId(), $castMember->getCharacter(), $position);
        }
    }

    public function updateCrew(int $movieId, Crew $tmdbCrew) : void
    {
        $this->movieProductionCompanyApi->deleteByMovieId($movieId);

        foreach ($tmdbCrew as $position => $crewMember) {
            $person = $this->createOrUpdatePersonByTmdbId(
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
    ) : MovieEntity {
        return $this->repository->updateDetails($id, $tagline, $overview, $originalLanguage, $releaseDate, $runtime, $tmdbVoteAverage, $tmdbVoteCount, $tmdbPosterPath, $imdbId);
    }

    public function updateGenres(int $movieId, Genre\GenreEntityList $genres) : void
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

    public function updateProductionCompanies(int $movieId, Company\CompanyEntityList $genres) : void
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

    private function createOrUpdatePersonByTmdbId(int $tmdbId, string $name, Gender $gender, ?string $knownForDepartment, ?string $posterPath) : Person\PersonEntity
    {
        $person = $this->personApi->findByTmdbId($tmdbId);

        if ($person === null) {
            return $this->personApi->create(
                $name,
                $gender,
                $knownForDepartment,
                $tmdbId,
                $posterPath,
            );
        }

        if ($person->getName() !== $name ||
            $person->getGender() !== $gender ||
            $person->getKnownForDepartment() !== $knownForDepartment ||
            $person->getTmdbPosterPath() !== $posterPath
        ) {
            $this->personApi->update($person->getId(), $name, $gender, $knownForDepartment, $tmdbId, $posterPath);
        }

        return $person;
    }
}
