<?php declare(strict_types=1);

namespace Movary\Application\Company;

class Entity
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly string $originCountry,
        private readonly int $tmdbId
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['origin_country'],
            $data['tmdb_id'],
        );
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getOriginCountry() : ?string
    {
        return $this->originCountry;
    }

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }
}
