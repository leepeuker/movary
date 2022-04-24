<?php declare(strict_types=1);

namespace Movary\Application\Genre;

class Entity
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly int $tmdbId
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            (int)$data['id'],
            $data['name'],
            (int)$data['tmdb_id'],
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

    public function getTmdbId() : int
    {
        return $this->tmdbId;
    }
}
