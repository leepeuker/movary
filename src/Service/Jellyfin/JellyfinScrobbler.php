<?php declare(strict_types=1);

namespace Movary\Service\Jellyfin;

use DateTime;
use DateTimeZone;
use Movary\Domain\Movie\MovieApi;
use Movary\Domain\User\UserApi;
use Movary\Service\Tmdb\SyncMovie;
use Movary\ValueObject\Date;
use Psr\Log\LoggerInterface;
use RuntimeException;

class JellyfinScrobbler
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly UserApi $userApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function processJellyfinWebhook(int $userId, array $payload) : void
    {
        $user = $this->userApi->fetchUser($userId);
        if ($user->getJellyfinScrobbleViews() === false) {
            $this->logger->debug('Jellyfin: Movie ignored because user does not want to scrobble views');

            return;
        }

        $notificationType = $payload['NotificationType'] ?? null;
        $movieName = $payload['Name'] ?? null;
        $timestamp = $payload['UtcTimestamp'] ?? null;
        $playedToCompletion = $payload['PlayedToCompletion'] ?? null;
        $tmdbId = $payload['Provider_tmdb'] ?? null;

        if ($notificationType !== 'PlaybackStop') {
            $this->logger->debug('Jellyfin: Movie ignored because notification type is not supported', ['notificationType' => $notificationType]);

            return;
        }

        if ($playedToCompletion === false) {
            $this->logger->debug('Jellyfin: Movie ignored because it was not played to completion', ['tmdbId' => $tmdbId, 'movieName' => $movieName]);

            return;
        }

        if ($tmdbId === null) {
            $this->logger->debug('Jellyfin: Movie ignored because it was missing tmdb id', ['movieName' => $movieName]);

            return;
        }

        $movie = $this->movieApi->findByTmdbId((int)$tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie((int)$tmdbId);
        }

        $watchDate = $this->getWatchDate($timestamp);

        $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $user->getId(), $watchDate);

        $this->logger->debug('Jellyfin: Scrobbled view [' . $watchDate . '] for movie: ' . $movie->getId());
    }

    private function getWatchDate(?string $timestamp) : Date
    {
        $timestampWithoutMicroseconds = preg_replace('/\.\d+Z/', '', (string)$timestamp);

        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s', (string)$timestampWithoutMicroseconds, new DateTimeZone('UTC'));
        if ($dateTime === false) {
            throw new RuntimeException('Could not build date time from: ' . $timestamp);
        }

        $dateTime->setTimezone((new DateTime)->getTimezone());

        return Date::createFromString($dateTime->format('Y-m-d'));
    }
}
