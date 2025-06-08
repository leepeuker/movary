<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Exception;
use Movary\Api\Tmdb\Dto\TmdbMovie;
use Movary\Api\Tmdb\Dto\TmdbProductionCompany;
use Movary\Domain\Company\CompanyApi;
use Movary\Domain\Company\CompanyEntity;
use Movary\Domain\Company\CompanyEntityList;
use Psr\Log\LoggerInterface;

class SyncProductionCompany
{
    public function __construct(
        private readonly CompanyApi $companyApi,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncMovaryProductionCompaniesFromTmdbMovie(TmdbMovie $movieDetails) : CompanyEntityList
    {
        $productionCompanies = CompanyEntityList::create();

        foreach ($movieDetails->getProductionCompanies() as $tmdbCompany) {
            $movaryCompany = $this->syncTmdbCompanyWithMovay($tmdbCompany);

            $productionCompanies->add($movaryCompany);
        }

        return $productionCompanies;
    }

    private function syncTmdbCompanyWithMovay(TmdbProductionCompany $tmdbCompany) : CompanyEntity
    {
        $movaryCompany = $this->companyApi->findByTmdbId($tmdbCompany->getId());

        if ($movaryCompany === null) {
            $movaryCompany = $this->companyApi->create(
                $tmdbCompany->getName(),
                $tmdbCompany->getOriginCountry(),
                $tmdbCompany->getId(),
            );

            $this->logger->debug(
                'TMDB: Created missing company',
                [
                    'movaryCompanyId' => $movaryCompany->getId(),
                    'tmdbCompanyId' => $tmdbCompany->getId(),
                ],
            );

            return $movaryCompany;
        }

        if ($tmdbCompany->getId() === $movaryCompany->getTmdbId() &&
            $tmdbCompany->getName() === $movaryCompany->getName() &&
            $tmdbCompany->getOriginCountry() === $movaryCompany->getOriginCountry()) {
            return $movaryCompany;
        }

        try {
            $movaryCompany = $this->companyApi->update(
                $movaryCompany->getId(),
                $tmdbCompany->getId(),
                $tmdbCompany->getName(),
                $tmdbCompany->getOriginCountry(),
            );
        } catch (Exception $exception) {
            $this->logger->warning(
                'TMDB: Could not update conflicting company data',
                [
                    'movaryCompanyId' => $movaryCompany->getId(),
                    'tmdbCompanyId' => $tmdbCompany->getId(),
                    'exception' => $exception,
                ],
            );

            return $movaryCompany;
        }

        $this->logger->debug(
            'TMDB: Updated conflicting company data',
            [
                'movaryCompanyId' => $movaryCompany->getId(),
                'tmdbCompanyId' => $tmdbCompany->getId(),
            ],
        );

        return $movaryCompany;
    }
}
