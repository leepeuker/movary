<?php declare(strict_types=1);

namespace Movary\Service\Plex;

use DateTime;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\Movie\MovieEntity;
use Movary\Domain\User\UserApi;
use Movary\Domain\User\UserEntity;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PlexScrobbler
{
    private const string MEDIA_RATE = 'media.rate';

    private const string MEDIA_SCROBBLE = 'media.scrobble';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSyncService,
    ) {
    }

    public function processPlexWebhook(int $userId, array $webHook) : void
    {
        $user = $this->userApi->fetchUser($userId);

        if (($webHook['event'] !== self::MEDIA_SCROBBLE && $webHook['event'] !== self::MEDIA_RATE)
            || $webHook['user'] === false
            || $webHook['Metadata']['librarySectionType'] !== 'movie') {
            return;
        }

        $tmdbId = null;
        foreach ($webHook['Metadata']['Guid'] as $guid) {
            if (str_starts_with($guid['id'], 'tmdb') === true) {
                $tmdbId = str_replace('tmdb://', '', $guid['id']);
            }
        }

        if ($tmdbId === null) {
            $this->logger->error('Could not extract tmdb id from webhook: ' . Json::encode($webHook));

            return;
        }

        $movie = $this->movieApi->findByTmdbId((int)$tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie((int)$tmdbId);
        }

        match (true) {
            $webHook['event'] === self::MEDIA_SCROBBLE => $this->logView($webHook, $movie, $user),
            $webHook['event'] === self::MEDIA_RATE => $this->logRating($webHook, $movie, $user),
        };
    }

    private function logRating(array $webHook, MovieEntity $movie, UserEntity $user) : void
    {
        if ($user->hasPlexScrobbleRatingsEnabled() === false) {
            return;
        }

        if (isset($webHook['rating']) === false) {
            throw new RuntimeException('Could not get rating from: ' . Json::encode($webHook));
        }

        $rating = PersonalRating::create((int)$webHook['rating']);

        $this->movieApi->updateUserRating($movie->getId(), $user->getId(), $rating);

        $this->logger->debug('Plex: Scrobbled rating [' . $rating . '] for movie: ' . $movie->getId());
    }

    private function logView(array $webHook, MovieEntity $movie, UserEntity $user) : void
    {
        if ($user->hasPlexScrobbleWatchesEnabled() === false) {
            return;
        }

        $dateTime = DateTime::createFromFormat('U', (string)$webHook['Metadata']['lastViewedAt']);
        if ($dateTime === false) {
            throw new RuntimeException('Could not build date time from: ' . $webHook['Metadata']['lastViewedAt']);
        }

        $watchDate = Date::createFromString($dateTime->format('Y-m-d'));

        $this->movieApi->addPlaysForMovieOnDate($movie->getId(), $user->getId(), $watchDate);

        $this->logger->debug('Plex: Scrobbled view [' . $watchDate . '] for movie "' . $movie->getTitle() . '" with id: ' . $movie->getId());
    }
}
