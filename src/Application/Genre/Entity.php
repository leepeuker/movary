<?php declare(strict_types=1);

namespace Movary\Application\Genre;

class Entity
{
    private int $id;

    private string $name;

    private int $tmdbId;

    private function __construct(int $id, string $name, int $tmdbId)
    {
        $this->id = $id;
        $this->name = $name;
        $this->tmdbId = $tmdbId;
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
}
