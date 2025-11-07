<?php declare(strict_types=1);

namespace Movary\Domain\Country;

class CountryApi
{
    public function __construct(
        private readonly CountryRepository $repository,
    ) {
    }

    public function getIso31661ToNameMap() : array
    {
        $map = [];

        foreach ($this->repository->fetchAll() as $country) {
            $map[$country->getIso31661()] = $country->getName();
        }

        return $map;
    }
}
