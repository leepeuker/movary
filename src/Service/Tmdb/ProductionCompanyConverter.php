<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\Dto\TmdbProductionCompany;
use Movary\Domain\Company\CompanyApi;
use Movary\Domain\Company\CompanyEntity;
use Movary\Domain\Company\CompanyEntityList;

class ProductionCompanyConverter
{
    public function __construct(private readonly CompanyApi $companyApi)
    {
    }

    public function getMovaryProductionCompaniesFromTmdbMovie(TmdbMovie $movieDetails) : CompanyEntityList
    {
        $productionCompanies = CompanyEntityList::create();

        foreach ($movieDetails->getProductionCompanies() as $tmdbCompany) {
            $company = $this->companyApi->findByTmdbId($tmdbCompany->getId());

            if ($company === null) {
                $company = $this->createMissingCompany($tmdbCompany);
            }

            $productionCompanies->add($company);
        }

        return $productionCompanies;
    }

    private function createMissingCompany(TmdbProductionCompany $tmdbCompany) : CompanyEntity
    {
        return $this->companyApi->create($tmdbCompany->getName(), $tmdbCompany->getOriginCountry(), $tmdbCompany->getId());
    }
}
