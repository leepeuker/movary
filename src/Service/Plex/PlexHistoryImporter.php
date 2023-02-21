<?php declare(strict_types=1);

namespace Movary\Service\Plex;

use Movary\Api\Plex\Dto\PlexItem;
use Movary\Api\Plex\Dto\PlexItemList;
use Movary\Api\Plex\Exception\PlexNoLibrariesAvailable;
use Movary\Api\Plex\PlexApi;
use Psr\Log\LoggerInterface;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;

class PlexHistoryImporter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MovieApi $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly PlexApi $plexApi
    ) {
    }

    /**
     * @throws PlexNoLibrariesAvailable
     */
    public function importPlexData(int $userId) : string
    {
        $unknownPlexItems = PlexItemList::create();
        $plexLibraries = $this->plexApi->fetchPlexLibraries();
        if($plexLibraries === null) {
            throw PlexNoLibrariesAvailable::create($userId);
        }
        foreach($plexLibraries as $library) {
            if($library['type'] !== 'movie') {
                continue;
            }
            $libraryKey = (int)$library['key'];
            $libraryWatchHistory = $this->plexApi->fetchPlexLibraryWatchedHistory($libraryKey);
            if($libraryWatchHistory === null) {
                continue;
            }
            foreach($libraryWatchHistory as $watchedItem) {
                $key = (int)$watchedItem['ratingKey'];
                $plexItem = $this->plexApi->fetchPlexItem($key);
                if($plexItem === null) {
                    continue;
                } else if($plexItem->getImdbId() === null) {
                    $unknownPlexItems->add($plexItem);
                    continue;
                }
                $this->importPlexMovie($plexItem, $userId);
            }
        }
        return Json::encode($unknownPlexItems);
    }

    private function importPlexMovie(PlexItem $plexItem, int $userId) : void
    {
        if($plexItem->getImdbId() === null || $plexItem->getLastViewedAt() === null) {
            return;
        }
        /** @phpstan-ignore-next-line */
        $movie = $this->movieApi->findByTmdbId($plexItem->getTmdbId());
        if($movie === null) {
            /** @phpstan-ignore-next-line */
            $movie = $this->tmdbMovieSyncService->syncMovie($plexItem->getTmdbId());

            $this->logger->debug('Plex: Missing movie created during import', ['movieId' => $movie->getId(), 'moveTitle' => $movie->getTitle()]);
        }
        /** @phpstan-ignore-next-line */
        $historyEntry = $this->movieApi->findHistoryEntryForMovieByUserOnDate($plexItem->getTmdbId(), $userId, $plexItem->getLastViewedAt());
        if ($historyEntry !== null) {
            $this->logger->info('Plex: Movie ignored because it was already imported.', [
                'movieId' => $plexItem->getTmdbId(),
                'movieTitle' => $plexItem->getTitle(),
                'watchDate' => $plexItem->getLastViewedAt(),
                'personalRating' => $plexItem->getUserRating(),
            ]);

            return;
        }
        $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $userId, $plexItem->getLastViewedAt());
        $this->movieApi->updateUserRating($movie->getId(), $userId, $plexItem->getUserRating());

        $this->logger->info('Plex: Movie watch date imported', [
            'movieId' => $plexItem->getTmdbId(),
            'moveTitle' => $plexItem->getTitle(),
            'watchDate' => $plexItem->getLastViewedAt(),
            'personalRating' => $plexItem->getUserRating(),
        ]);
    }
}