<?php declare(strict_types=1);

namespace Movary\Domain\Country;

use Doctrine\DBAL\Connection;

class CountryRepository
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    public function fetchAll() : CountryEntityList
    {
        $data = $this->dbConnection->fetchAllAssociative('SELECT * FROM `country` ORDER BY english_name');

        return CountryEntityList::createFromArray($data);
    }
}
