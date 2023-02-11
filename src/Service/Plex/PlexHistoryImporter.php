<?php declare(strict_types=1);

namespace Movary\Service\Plex;

use Movary\Api\Plex\Dto\PlexItemList;
use Movary\Api\Plex\PlexApi;
use Psr\Log\LoggerInterface;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\Service\Tmdb\SyncMovie;

class PlexHistoryImporter
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly PlexApi $plexApi
    ) {
    }

    public function importPlexData(int $userId, array $plexItems) : void
    {
        $plexItemList = $this->createPlexItemList($plexItems);
        
    }

    public function createPlexItemList(array $plexItems) : PlexItemList
    {
        $plexItemList = PlexItemList::create();
        foreach($plexItems as $item) {
            $plexItemId = str_replace('/library/metadata', '', $item['ratingKey']);
            $plexItem = $this->plexApi->fetchPlexItem((int)$plexItemId);
            $plexItemList->add($plexItem);
        }
        return $plexItemList;
    }
}