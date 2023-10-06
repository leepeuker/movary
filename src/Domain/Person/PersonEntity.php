<?php declare(strict_types=1);

namespace Movary\Domain\Person;

use Movary\ValueObject\Date;
use Movary\ValueObject\DateTime;
use Movary\ValueObject\Gender;

class PersonEntity
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly Gender $gender,
        private readonly ?string $knownForDepartment,
        private readonly int $tmdbId,
        private readonly ?string $imdbId,
        private readonly ?string $posterPath,
        private readonly ?string $tmdbPosterPath,
        private readonly ?string $biography,
        private readonly ?Date $birthDate,
        private readonly ?Date $deathDate,
        private readonly ?string $placeOfBirth,
        private readonly bool $hiddenInTopLists,
        private readonly ?DateTime $updatedAtTmdb,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['name'],
            Gender::createFromInt((int)$data['gender']),
            $data['known_for_department'],
            $data['tmdb_id'],
            $data['imdb_id'],
            $data['poster_path'],
            $data['tmdb_poster_path'],
            empty($data['biography']) === true ? null : $data['biography'],
            empty($data['birth_date']) === true ? null : Date::createFromString($data['birth_date']),
            empty($data['death_date']) === true ? null : Date::createFromString($data['death_date']),
            $data['place_of_birth'],
            $data['hidden_in_top_lists'] ?? false,
            empty($data['updated_at_tmdb']) === true ? null : DateTime::createFromString($data['updated_at_tmdb']),
        );
    }

    public function getBiography() : ?string
    {
        return $this->biography;
    }

    public function getBirthDate() : ?Date
    {
        return $this->birthDate;
    }

    public function getDeathDate() : ?Date
    {
        return $this->deathDate;
    }

    public function getGender() : Gender
    {
        return $this->gender;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getImdbId() : ?string
    {
        return $this->imdbId;
    }

    public function getKnownForDepartment() : ?string
    {
        return $this->knownForDepartment;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getPlaceOfBirth() : ?string
    {
        return $this->placeOfBirth;
    }

    public function getPosterPath() : ?string
    {
        return $this->posterPath;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }

    public function getTmdbPosterPath() : ?string
    {
        return $this->tmdbPosterPath;
    }

    public function getUpdatedAtTmdb() : ?DateTime
    {
        return $this->updatedAtTmdb;
    }

    public function isHiddenInTopLists() : bool
    {
        return $this->hiddenInTopLists;
    }
}
