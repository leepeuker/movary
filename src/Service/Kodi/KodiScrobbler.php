<?php declare(strict_types=1);

namespace Movary\Service\Kodi;

use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\Service\Tmdb\SyncMovie;
use Psr\Log\LoggerInterface;

class KodiScrobbler
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly KodiWebhookDtoMapper $webhookDtoMapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function processKodiWebhook(int $userId, array $payload) : void
    {
        $user = $this->userApi->fetchUser($userId);
        if ($user->hasKodiScrobbleWatchesEnabled() === false) {
            $this->logger->debug('Kodi: Movie ignored because user has scrobbling of watches disabled');

            return;
        }

        $webhookDto = $this->webhookDtoMapper->map($payload);
        if ($webhookDto === null) {
            return;
        }

        $tmdbId = $webhookDto->getTmdbId();
        if ($tmdbId === null) {
            $this->logger->warning('Kodi: Movie ignored because it was missing tmdb id', ['movieName' => $webhookDto->getMovieName()]);

            return;
        }

        $movie = $this->movieApi->findByTmdbId($tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie($tmdbId);

            $this->logger->debug('Kodi: Created not yet existing watched movie', ['movieId' => $movie->getId(), 'movieTitle' => $movie->getTitle()]);
        }

        $this->movieApi->addPlaysForMovieOnDate($movie->getId(), $user->getId(), $webhookDto->getWatchDate());

        $this->logger->info('Kodi: Scrobbled movie watch date', [
            'movieId' => $movie->getId(),
            'movieTitle' => $movie->getTitle(),
            'watchDate' => (string)$webhookDto->getWatchDate()
        ]);
    }
}
