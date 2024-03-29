<?php declare(strict_types=1);

namespace Movary\Service\Tmdb;

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
        private readonly LoggerInterface $logger,
    ) {
    }

    public function syncMovies(?int $maxAgeInHours = null, ?int $movieCountSyncThreshold = null, ?array $ids = []) : void
    {
        $movies = $this->movieApi->fetchAllOrderedByLastUpdatedAtTmdbAsc($movieCountSyncThreshold, $ids);

        foreach ($movies as $movie) {
            $movie = MovieEntity::createFromArray($movie);

            $updatedAtTmdb = $movie->getUpdatedAtTmdb();
            if ($maxAgeInHours !== null &&
                $updatedAtTmdb !== null &&
                $this->syncExpired($updatedAtTmdb, $maxAgeInHours) === false) {
                continue;
            }

            try {
                $this->syncMovieService->syncMovie($movie->getTmdbId());
            } catch (Throwable $t) {
                $this->logger->warning(
                    'TMDB: Could not update movie.',
                    [
                        'exception' => $t,
                        'movieId' => $movie->getId(),
                        'tmdbId' => $movie->getTmdbId(),
                    ],
                );
            }
        }
    }

    private function syncExpired(DateTime $updatedAtTmdb, int $maxAgeInDays) : bool
    {
        return DateTime::create()->differenceInHours($updatedAtTmdb) > $maxAgeInDays;
    }
}
