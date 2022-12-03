<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Movary\Api\Tmdb;
use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\Dto\TmdbProductionCompany;
use Movary\Domain\Company\CompanyApi;
use Movary\Domain\Company\CompanyEntity;
use Movary\Domain\Company\CompanyEntityList;

class ProductionCompanyConverter
{
    public function __construct(private readonly CompanyApi $companyApi, private readonly Tmdb\TmdbApi $tmdbApi)
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
        try {
            return $this->companyApi->create($tmdbCompany->getName(), $tmdbCompany->getOriginCountry(), $tmdbCompany->getId());
        } catch (UniqueConstraintViolationException $e) {
            $companyCausingConstraintViolation = $this->companyApi->findByNameAndOriginCountry($tmdbCompany->getName(), $tmdbCompany->getOriginCountry());

            if ($companyCausingConstraintViolation === null) {
                throw $e;
            }

            $this->fixUniqueConstraintViolation($companyCausingConstraintViolation);
        }

        return $this->companyApi->create($tmdbCompany->getName(), $tmdbCompany->getOriginCountry(), $tmdbCompany->getId());
    }

    private function fixUniqueConstraintViolation(CompanyEntity $companyCausingConstraintViolation) : void
    {
        try {
            // The unique constraint violation indicates that the local company is no longer matching the remote tmdb company
            $tmdbCompany = $this->tmdbApi->fetchCompany($companyCausingConstraintViolation->getTmdbId());
        } catch (Tmdb\Exception\TmdbResourceNotFound $e) {
            // Remote company no longer exists, so we can delete it locally
            $this->companyApi->deleteByTmdbId($companyCausingConstraintViolation->getTmdbId());

            return;
        }

        $this->companyApi->update($tmdbCompany->getId(), $tmdbCompany->getName(), $tmdbCompany->getOriginCountry());
    }
}
