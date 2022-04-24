<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class Genre
{
    private function __construct(
        private readonly int $id,
        private readonly string $name
    ) {
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
