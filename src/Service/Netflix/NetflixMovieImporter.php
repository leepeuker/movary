<?php declare(strict_types=1);

namespace Movary\Service\Netflix;

use Movary\Domain\Movie\MovieApi;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;

class NetflixMovieImporter
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function importWatchDates(int $userId, array $items) : void
    {
        $this->logger->debug('Netflix: Imported started');

        foreach ($items as $item) {
            $this->importWatchDate($userId, $item);
        }

        $this->logger->debug('Netflix: Imported finished');
    }

    private function importWatchDate(int $userId, array $data) : void
    {
        if (isset($data['watchDate'], $data['tmdbId'], $data['dateFormat']) === false) {
            $this->logger->warning('Netflix: Could not import incomplete movie watch date', ['incompleteData' => Json::encode($data)]);

            return;
        }

        $watchDate = Date::createFromStringAndFormat($data['watchDate'], $data['dateFormat']);

        $tmdbId = (int)$data['tmdbId'];
        $personalRating = empty($data['personalRating']) === true ? null : PersonalRating::create((int)$data['personalRating']);

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);

            $this->logger->debug('Netflix: Missing movie created during import', ['movieId' => $movie->getId(), 'moveTitle' => $movie->getTitle()]);
        }

        $historyEntry = $this->movieApi->findHistoryEntryForMovieByUserOnDate($movie->getId(), $userId, $watchDate);
        if ($historyEntry !== null) {
            $this->logger->info('Netflix: Movie watch date ignored because it was already set.', [
                'movieId' => $movie->getId(),
                'movieTitle' => $movie->getTitle(),
                'watchDate' => $watchDate,
                'personalRating' => $personalRating,
            ]);

            return;
        }

        $this->movieApi->addPlaysForMovieOnDate($movie->getId(), $userId, $watchDate);
        $this->movieApi->updateUserRating($movie->getId(), $userId, $personalRating);

        $this->logger->info('Netflix: Movie watch date imported', [
            'movieId' => $movie->getId(),
            'moveTitle' => $movie->getTitle(),
            'watchDate' => $watchDate,
            'personalRating' => $personalRating,
        ]);
    }
}
