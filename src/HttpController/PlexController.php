<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Domain\User\Service\Authentication;
use Movary\Domain\User\UserApi;
use Movary\Domain\Movie;
use Movary\Domain\Movie\MovieApi;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Movary\ValueObject\PersonalRating;
use Psr\Log\LoggerInterface;

class PlexController
{
    private const MEDIA_RATE = 'media.rate';

    private const MEDIA_SCROBBLE = 'media.scrobble';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MovieApi $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly UserApi $userApi,
        private readonly Authentication $authenticationService,
        private readonly bool $plexEnableScrobbleWebhook,
        private readonly bool $plexEnableRatingWebhook,
    ) {
    }

    public function deletePlexWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $this->userApi->deletePlexWebhookId($this->authenticationService->getCurrentUserId());

        return Response::create(StatusCode::createOk());
    }

    public function getPlexWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $plexWebhookId = $this->userApi->findPlexWebhookId($_SESSION['userId']);

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }

    public function handlePlexWebhook(Request $request) : Response
    {
        $webhookId = $request->getRouteParameters()['id'];

        $userId = $this->userApi->findUserIdByPlexWebhookId($webhookId);

        if ($userId === null) {
            return Response::createNotFound();
        }

        $requestPayload = $request->getPostParameters()['payload'];

        $this->logger->debug($requestPayload);

        $webHook = Json::decode((string)$requestPayload);

        if (($webHook['event'] !== self::MEDIA_SCROBBLE && $webHook['event'] !== self::MEDIA_RATE)
            || $webHook['user'] === false
            || $webHook['Metadata']['librarySectionType'] !== 'movie') {
            return Response::create(StatusCode::createOk());
        }

        $tmdbId = null;
        foreach ($webHook['Metadata']['Guid'] as $guid) {
            if (str_starts_with($guid['id'], 'tmdb') === true) {
                $tmdbId = str_replace('tmdb://', '', $guid['id']);
            }
        }

        if ($tmdbId === null) {
            $this->logger->error('Could not extract tmdb id from webhook: ' . Json::encode($webHook));

            return Response::create(StatusCode::createOk());
        }

        $movie = $this->movieApi->findByTmdbId((int)$tmdbId);

        if ($movie === null) {
            $movie = $this->tmdbMovieSyncService->syncMovie((int)$tmdbId);
        }

        return match (true) {
            $webHook['event'] === self::MEDIA_SCROBBLE => $this->logView($webHook, $movie, $userId),
            $webHook['event'] === self::MEDIA_RATE => $this->logRating($webHook, $movie, $userId),
        };
    }

    public function regeneratePlexWebhookId() : Response
    {
        if ($this->authenticationService->isUserAuthenticated() === false) {
            return Response::createFoundRedirect('/');
        }

        $plexWebhookId = $this->userApi->regeneratePlexWebhookId($this->authenticationService->getCurrentUserId());

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }

    private function logRating(array $webHook, Movie\MovieEntity $movie, int $userId) : Response
    {
        if ($this->plexEnableRatingWebhook === false) {
            return Response::create(StatusCode::createOk());
        }

        if (isset($webHook['rating']) === false) {
            throw new \RuntimeException('Could not get rating from: ' . Json::encode($webHook));
        }

        $rating = PersonalRating::create((int)$webHook['rating']);

        $this->movieApi->updateUserRating($movie->getId(), $userId, $rating);

        return Response::create(StatusCode::createOk());
    }

    private function logView(array $webHook, Movie\MovieEntity $movie, int $userId) : Response
    {
        if ($this->plexEnableScrobbleWebhook === false) {
            return Response::create(StatusCode::createOk());
        }

        $dateTime = \DateTime::createFromFormat('U', (string)$webHook['Metadata']['lastViewedAt']);
        if ($dateTime === false) {
            throw new \RuntimeException('Could not build date time from: ' . $webHook['Metadata']['lastViewedAt']);
        }

        $watchDate = Date::createFromString($dateTime->format('Y-m-d'));

        $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $userId, $watchDate);

        return Response::create(StatusCode::createOk());
    }
}
