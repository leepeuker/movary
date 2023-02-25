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
        $tmdbId = $plexItem->getTmdbId();
        $lastViewedAt = $plexItem->getLastViewedAt();
        $userRating = $plexItem->getUserRating();
        if($tmdbId === null || $lastViewedAt === null) {
            $this->logger->info('Plex: Movie ignored because it did not contain a TMDB ID.', ['movieTitle' => $plexItem->getTitle()]);
            return;
        }
        $movie = $this->movieApi->findByTmdbId($tmdbId);
        if($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);

            $this->logger->debug('Plex: Missing movie created during import', ['movieId' => $movie->getId(), 'moveTitle' => $movie->getTitle()]);
        }
        $historyEntry = $this->movieApi->findHistoryEntryForMovieByUserOnDate($tmdbId, $userId, $lastViewedAt);
        if ($historyEntry !== null) {
            $this->logger->info('Plex: Movie ignored because it was already imported.', [
                'movieId' => $tmdbId,
                'movieTitle' => $plexItem->getTitle(),
                'watchDate' => $lastViewedAt,
                'personalRating' => $userRating,
            ]);

            return;
        }
        $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $userId, $lastViewedAt);
        if($userRating !== null) {
            $this->movieApi->updateUserRating($movie->getId(), $userId, $userRating);
        }

        $this->logger->info('Plex: Movie watch date imported', [
            'movieId' => $tmdbId,
            'moveTitle' => $plexItem->getTitle(),
            'watchDate' => $lastViewedAt,
            'personalRating' => $userRating,
        ]);
    }
}