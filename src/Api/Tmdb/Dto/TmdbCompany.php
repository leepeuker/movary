<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class TmdbCompany
{
    private function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $originCountry,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self($data['id'], $data['name'], $data['origin_country']);
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
}
