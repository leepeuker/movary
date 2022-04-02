<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\ValueObject;

class Person
{
    // private const GENDER_UNKNOWN = 0;

    // private const GENDER_FEMALE = 1;

    // private const GENDER_MALE = 2;

    private string $creditId;

    private int $gender;

    private string $knownForDepartment;

    private string $name;

    private string $originalName;

    private float $popularity;

    private ?string $profilePath;

    private int $tmdbId;

    private function __construct(
        int $tmdbId,
        string $name,
        string $originalName,
        int $gender,
        float $popularity,
        string $knownForDepartment,
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
            $data['gender'],
            $data['popularity'],
            $data['known_for_department'],
            $data['profile_path'],
            $data['credit_id'],
        );
    }

    public function getCreditId() : string
    {
        return $this->creditId;
    }

    public function getGender() : int
    {
        return $this->gender;
    }

    public function getKnownForDepartment() : string
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
