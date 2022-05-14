<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Doctrine\DBAL;
use Movary\Api\Tmdb;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\Application\SyncLog\Repository;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;

class SyncMovieDetails
{
    public function __construct(
        private readonly Tmdb\Api $api,
        private readonly Movie\Service\Select $movieSelectService,
        private readonly Movie\Service\Update $movieUpdateService,
        private readonly Genre\Service\Select $genreSelectService,
        private readonly Genre\Service\Create $genreCreateService,
        private readonly Company\Service\Select $companySelectService,
        private readonly Company\Service\Create $companyCreateService,
        private readonly DBAL\Connection $dbConnection,
        private readonly LoggerInterface $logger,
        private readonly Repository $scanLogRepository
    ) {
    }

    public function execute(?int $maxAgeInHours, ?int $movieCountSyncThreshold) : void
    {
        $movies = $this->movieSelectService->fetchAllOrderedByLastUpdatedAtTmdbDesc();

        $movieCountSynced = 0;

        foreach ($movies as $movie) {
            if ($movieCountSyncThreshold !== null && $movieCountSynced >= $movieCountSyncThreshold) {
                continue;
            }

            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($maxAgeInHours !== null && $updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb, $maxAgeInHours) === false) {
                continue;
            }

            $this->dbConnection->beginTransaction();

            try {
                $this->updateDetails($movie);
                $this->updateCredits($movie);
                $this->dbConnection->commit();
            } catch (\Throwable $t) {
                $this->dbConnection->rollBack();
                $this->logger->error('Could not sync credits for movie with id "' . $movie->getId() . '". Error: ' . $t->getMessage(), ['exception' => $t]);
            }

            $movieCountSynced++;
        }

        $this->scanLogRepository->insertLogForTmdbSync();
    }

    public function getGenres(Tmdb\Dto\Movie $movieDetails) : Genre\EntityList
    {
        $genres = Genre\EntityList::create();

        foreach ($movieDetails->getGenres() as $tmdbGenre) {
            $genre = $this->genreSelectService->findByTmdbId($tmdbGenre->getId());

            if ($genre === null) {
                $genre = $this->genreCreateService->create($tmdbGenre->getName(), $tmdbGenre->getId());
            }

            $genres->add($genre);
        }

        return $genres;
    }

    public function getProductionCompanies(Tmdb\Dto\Movie $movieDetails) : Company\EntityList
    {
        $productionCompany = Company\EntityList::create();

        foreach ($movieDetails->getProductionCompanies() as $tmdbCompany) {
            $company = $this->companySelectService->findByTmdbId($tmdbCompany->getId());

            if ($company === null) {
                $company = $this->companyCreateService->create($tmdbCompany->getName(), $tmdbCompany->getOriginCountry(), $tmdbCompany->getId());
            }

            $productionCompany->add($company);
        }

        return $productionCompany;
    }

    public function updateCredits(Movie\Entity $movie) : void
    {
        $credits = $this->api->getMovieCredits($movie->getTmdbId());

        $this->movieUpdateService->updateCast($movie->getId(), $credits->getCast());
        $this->movieUpdateService->updateCrew($movie->getId(), $credits->getCrew());
    }

    public function updateDetails(Movie\Entity $movie) : void
    {
        $movieDetails = $this->api->getMovieDetails($movie->getTmdbId());

        $this->movieUpdateService->updateDetails(
            $movie->getId(),
            $movieDetails->getTagline(),
            $movieDetails->getOverview(),
            $movieDetails->getOriginalLanguage(),
            $movieDetails->getReleaseDate(),
            $movieDetails->getRuntime(),
            $movieDetails->getVoteAverage(),
            $movieDetails->getVoteCount(),
            $movieDetails->getPosterPath(),
        );

        $this->movieUpdateService->updateGenres($movie->getId(), $this->getGenres($movieDetails));
        $this->movieUpdateService->updateProductionCompanies($movie->getId(), $this->getProductionCompanies($movieDetails));
    }

    private function syncExpired(DateTime $updatedAtTmdb, int $maxAgeInDays = null) : bool
    {
        return DateTime::create()->diff($updatedAtTmdb)->getHours() > $maxAgeInDays;
    }
}
