<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class Genre
{
    private int $id;

    private string $name;

    private function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['name'],
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
