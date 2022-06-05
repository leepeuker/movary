<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Movary\Api\Tmdb;
use Movary\Application\Company\Api;
use Movary\Application\Company\EntityList;

class ProductionCompanyConverter
{
    public function __construct(private readonly Api $companyApi)
    {
    }

    public function getMovaryProductionCompaniesFromTmdbMovie(Tmdb\Dto\Movie $movieDetails) : EntityList
    {
        $productionCompany = EntityList::create();

        foreach ($movieDetails->getProductionCompanies() as $tmdbCompany) {
            $company = $this->companyApi->findByTmdbId($tmdbCompany->getId());

            if ($company === null) {
                $company = $this->companyApi->create($tmdbCompany->getName(), $tmdbCompany->getOriginCountry(), $tmdbCompany->getId());
            }

            $productionCompany->add($company);
        }

        return $productionCompany;
    }
}
