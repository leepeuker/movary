<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

use Doctrine\DBAL;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;
use Throwable;

class SyncMovies
{
    public function __construct(
        private readonly SyncMovie $syncMovieService,
        private readonly MovieApi $movieApi,
        private readonly DBAL\Connection $dbConnection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncMovies(?int $maxAgeInHours = null, ?int $movieCountSyncThreshold = null) : void
    {
        $movies = $this->movieApi->fetchAllOrderedByLastUpdatedAtTmdbAsc($movieCountSyncThreshold);

        foreach ($movies as $movie) {
            $movie = MovieEntity::createFromArray($movie);

            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($maxAgeInHours !== null && $updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb, $maxAgeInHours) === false) {
                continue;
            }

            $this->dbConnection->beginTransaction();

            try {
                $this->syncMovieService->syncMovie($movie->getTmdbId());
            } catch (Throwable $t) {
                $this->dbConnection->rollBack();
                $this->logger->error('Could not sync credits for movie with id "' . $movie->getId() . '". Error: ' . $t->getMessage(), ['exception' => $t]);
            }

            $this->dbConnection->commit();
        }
    }

    private function syncExpired(DateTime $updatedAtTmdb, int $maxAgeInDays = null) : bool
    {
        return DateTime::create()->diffInHours($updatedAtTmdb) > $maxAgeInDays;
    }
}
