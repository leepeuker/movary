<?php declare(strict_types=1);

namespace Movary\Application\Person;

use Movary\ValueObject\Gender;

class Entity
{
    private Gender $gender;

    private int $id;

    private string $knownForDepartment;

    private string $name;

    private int $tmdbId;

    private function __construct(
        int $id,
        string $name,
        Gender $gender,
        string $knownForDepartment,
        int $tmdbId,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->gender = $gender;
        $this->knownForDepartment = $knownForDepartment;
        $this->tmdbId = $tmdbId;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['name'],
            Gender::createFromInt((int)$data['gender']),
            $data['known_for_department'],
            $data['tmdb_id'],
        );
    }

    public function getGender() : Gender
    {
        return $this->gender;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getKnownForDepartment() : string
    {
        return $this->knownForDepartment;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }
}
