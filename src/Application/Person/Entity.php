<?php declare(strict_types=1);

namespace Movary\Application\Person;

use Movary\ValueObject\Gender;

class Entity
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly Gender $gender,
        private readonly ?string $knownForDepartment,
        private readonly int $tmdbId,
        private readonly ?string $posterPath,
        private readonly ?string $tmdbPosterPath,
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
            $data['poster_path'],
            $data['tmdb_poster_path'],
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

    public function getKnownForDepartment() : ?string
    {
        return $this->knownForDepartment;
    }

    public function getName() : string
    {
        return $this->name;
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
}
