<?php declare(strict_types=1);

namespace Movary\HttpController;

use Movary\Application\Movie;
use Movary\Application\Service\Tmdb\SyncMovie;
use Movary\Application\SessionService;
use Movary\Application\User\Api;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;
use Movary\ValueObject\Http\StatusCode;
use Psr\Log\LoggerInterface;

class PlexController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Movie\Api $movieApi,
        private readonly SyncMovie $tmdbMovieSyncService,
        private readonly Api $userApi,
        private readonly SessionService $sessionService
    ) {
    }

    public function getPlexWebhookId() : Response
    {
        if ($this->sessionService->isCurrentUserLoggedIn() === false) {
            return Response::createFoundRedirect('/');
        }

        $plexWebhookId = $this->userApi->findPlexWebhookId();

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }

    public function handlePlexWebhook(Request $request) : Response
    {
        if ($request->getRouteParameters()['id'] !== $this->userApi->findPlexWebhookId()) {
            return Response::createNotFound();
        }

        $webHook = Json::decode($request->getPostParameters()['payload']);

        if ($webHook['event'] !== 'media.scrobble' || $webHook['user'] === false || $webHook['Metadata']['librarySectionType'] !== 'movie') {
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

        $dateTime = \DateTime::createFromFormat('U', (string)$webHook['Metadata']['lastViewedAt']);
        if ($dateTime === false) {
            throw new \RuntimeException('Could not build date time from: ' . $webHook['Metadata']['lastViewedAt']);
        }

        $watchDate = Date::createFromString($dateTime->format('Y-m-d'));

        $this->movieApi->increaseHistoryPlaysForMovieOnDate($movie->getId(), $watchDate);

        return Response::create(StatusCode::createOk());
    }

    public function regeneratePlexWebhookId() : Response
    {
        if ($this->sessionService->isCurrentUserLoggedIn() === false) {
            return Response::createFoundRedirect('/');
        }

        $plexWebhookId = $this->userApi->regeneratePlexWebhookId();

        return Response::createJson(Json::encode(['id' => $plexWebhookId]));
    }
}
