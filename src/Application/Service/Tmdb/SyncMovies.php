<?php declare(strict_types=1);

namespace Movary\Application\Service\Tmdb;

use Doctrine\DBAL;
use Movary\Application\Movie;
use Movary\Application\SyncLog\Repository;
use Movary\ValueObject\DateTime;
use Psr\Log\LoggerInterface;

class SyncMovies
{
    public function __construct(
        private readonly SyncMovie $syncMovieService,
        private readonly Movie\Api $movieApi,
        private readonly DBAL\Connection $dbConnection,
        private readonly LoggerInterface $logger,
        private readonly Repository $scanLogRepository,
    ) {
    }

    public function syncMovies(?int $maxAgeInHours, ?int $movieCountSyncThreshold) : void
    {
        $movies = $this->movieApi->fetchAllOrderedByLastUpdatedAtTmdbDesc();

        $movieCountSynced = 0;

        foreach ($movies as $movie) {
            if ($movieCountSyncThreshold !== null && $movieCountSynced >= $movieCountSyncThreshold) {
                continue;
            }

            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($maxAgeInHours !== null && $updatedAtTmdb !== null && $this->syncExpired($updatedAtTmdb, $maxAgeInHours) === false) {
                continue;
            }

            try {
                $this->syncMovieService->syncMovie($movie->getTmdbId());
            } catch (\Throwable $t) {
                $this->dbConnection->rollBack();
                $this->logger->error('Could not sync credits for movie with id "' . $movie->getId() . '". Error: ' . $t->getMessage(), ['exception' => $t]);
            }

            $movieCountSynced++;
        }

        $this->scanLogRepository->insertLogForTmdbSync();
    }

    private function syncExpired(DateTime $updatedAtTmdb, int $maxAgeInDays = null) : bool
    {
        return DateTime::create()->diff($updatedAtTmdb)->getHours() > $maxAgeInDays;
    }
}
