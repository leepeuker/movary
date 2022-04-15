<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

use Movary\ValueObject\Gender;

class Person
{
    private string $creditId;

    private Gender $gender;

    private ?string $knownForDepartment;

    private string $name;

    private string $originalName;

    private float $popularity;

    private ?string $profilePath;

    private int $tmdbId;

    private function __construct(
        int $tmdbId,
        string $name,
        string $originalName,
        Gender $gender,
        float $popularity,
        ?string $knownForDepartment,
        ?string $profilePath,
        string $creditId
    ) {
        $this->tmdbId = $tmdbId;
        $this->name = $name;
        $this->originalName = $originalName;
        $this->gender = $gender;
        $this->popularity = $popularity;
        $this->knownForDepartment = $knownForDepartment;
        $this->profilePath = $profilePath;
        $this->creditId = $creditId;
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

    public function getProfilePath() : ?string
    {
        return $this->profilePath;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }
}
