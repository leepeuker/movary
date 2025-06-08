<?php declare(strict_types=1);

namespace Movary\Domain\Country;

class CountryEntity
{
    private function __construct(
        private readonly string $iso31661,
        private readonly string $name,
    ) {
    }

    public static function createFromArray(array $data) : self
    {
        return new self(
            $data['iso_3166_1'],
            $data['english_name'],
        );
    }

    public function getIso31661() : string
    {
        return $this->iso31661;
    }

    public function getName() : string
    {
        return $this->name;
    }
}
