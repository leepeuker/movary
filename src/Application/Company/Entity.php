<?php declare(strict_types=1);

namespace Movary\Application\Company;

class Entity
{
    private int $id;

    private string $name;

    private ?string $originCountry;

    private int $tmdbId;

    private function __construct(
        int $id,
        string $name,
        string $originCountry,
        int $tmdbId
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->originCountry = $originCountry;
        $this->tmdbId = $tmdbId;
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
