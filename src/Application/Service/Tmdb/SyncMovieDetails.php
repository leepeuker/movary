<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Doctrine\DBAL;
use Movary\Api\Tmdb;
use Movary\Application\Company;
use Movary\Application\Genre;
use Movary\Application\Movie;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;

class SyncMovieDetails
{
    private const SYNC_VALIDITY_TIME_IN_DAYS = 7;

    public function __construct(
        private readonly Tmdb\Api $api,
        private readonly Movie\Service\Select $movieSelectService,
        private readonly Movie\Service\Update $movieUpdateService,
        private readonly Genre\Service\Select $genreSelectService,
        private readonly Genre\Service\Create $genreCreateService,
        private readonly Company\Service\Select $companySelectService,
        private readonly Company\Service\Create $companyCreateService,
        private readonly DBAL\Connection $dbConnection,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(bool $forceSync = false) : void
    {
        $movies = $this->movieSelectService->fetchAll();

        foreach ($movies as $movie) {
            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($forceSync === false && $updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb) === false) {
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
        }
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

    private function syncExpired(DateTime $updatedAtTmdb) : bool
    {
        return DateTime::create()->diff($updatedAtTmdb)->getDays() > self::SYNC_VALIDITY_TIME_IN_DAYS;
    }
}
