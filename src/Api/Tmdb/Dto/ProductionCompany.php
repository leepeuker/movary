<?php declare(strict_types=1);

namespace Movary\Api\Tmdb\Dto;

class ProductionCompany
{
    private int $id;

    private string $name;

    private ?string $originCountry;

    private function __construct(int $id, string $name, ?string $originCountry)
    {
        $this->id = $id;
        $this->name = $name;
        $this->originCountry = $originCountry;
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['origin_country'],
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
}
