<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\Date;
use Movary\ValueObject\Gender;

class TmdbPerson
{
    private function __construct(
        private readonly int $tmdbId,
        private readonly ?string $imdbId,
        private readonly string $name,
        private readonly ?string $biography,
        private readonly ?Date $birthDate,
        private readonly ?Date $deathDate,
        private readonly Gender $gender,
        private readonly float $popularity,
        private readonly ?string $knownForDepartment,
        private readonly ?string $profilePath,
        private readonly ?string $placeOfBirth,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['imdb_id'],
            $data['name'],
            empty($data['biography']) === true ? null : $data['biography'],
            empty($data['birthday']) === true ? null : Date::createFromString($data['birthday']),
            empty($data['deathday']) === true ? null : Date::createFromString($data['deathday']),
            Gender::createFromInt($data['gender']),
            $data['popularity'],
            empty($data['known_for_department']) === true ? null : $data['known_for_department'],
            $data['profile_path'],
            empty($data['place_of_birth']) === true ? null : $data['place_of_birth'],
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

    public function getPopularity() : float
    {
        return $this->popularity;
    }

    public function getProfilePath() : ?string
    {
        return $this->profilePath;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }
}
