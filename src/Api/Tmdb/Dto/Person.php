<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\Gender;

class Person
{
    private function __construct(
        private readonly int $tmdbId,
        private readonly string $name,
        private readonly string $originalName,
        private readonly Gender $gender,
        private readonly float $popularity,
        private readonly ?string $knownForDepartment,
        private readonly ?string $profilePath,
        private readonly string $creditId,
        private readonly ?string $posterPath
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['original_name'],
            Gender::createFromInt($data['gender']),
            $data['popularity'],
            empty($data['known_for_department']) === true ? null : $data['known_for_department'],
            $data['profile_path'],
            $data['credit_id'],
            $data['profile_path'],
        );
    }

    public function getCreditId() : string
    {
        return $this->creditId;
    }

    public function getGender() : Gender
    {
        return $this->gender;
    }

    public function getKnownForDepartment() : ?string
    {
        return $this->knownForDepartment;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getOriginalName() : string
    {
        return $this->originalName;
    }

    public function getPopularity() : float
    {
        return $this->popularity;
    }

    public function getPosterPath() : ?string
    {
        return $this->posterPath;
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
