<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\PlayedResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
use Movary\ValueObject\Date;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class PlayedController
{
    public function __construct(
        private readonly MovieApi $movieApi,
        private readonly PaginationElementsCalculator $paginationElementsCalculator,
        private readonly PlayedRequestMapper $playedRequestMapper,
        private readonly PlayedResponseMapper $playedResponseMapper,
        private readonly RequestMapper $requestMapper,
    ) {
    }

    public function addToPlayed(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $playedAdditions = Json::decode($request->getBody());

        foreach ($playedAdditions as $playAddition) {
            $movieId = (int)$playAddition['movaryId'];
            $watchDates = $playAddition['watchDates'] ?? [];

            foreach ($watchDates as $watchDate) {
                $this->movieApi->addPlaysForMovieOnDate(
                    $movieId,
                    $userId,
                    $watchDate['watchedAt'] !== null ? Date::createFromString($watchDate['watchedAt']) : null,
                    $watchDate['plays'] ?? 1,
                    $watchDate['comment'] ?? null,
                );
            }
        }

        return Response::createNoContent();
    }

    public function deleteFromPlayed(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $playedDeletions = Json::decode($request->getBody());

        foreach ($playedDeletions as $playedDeletion) {
            $movieId = (int)$playedDeletion['movaryId'];
            $watchDates = $playedDeletion['watchDates'] ?? [];

            if (count($watchDates) === 0) {
                $this->movieApi->deleteHistoryById(
                    $movieId,
                    $userId,
                );

                continue;
            }

            foreach ($watchDates as $date) {
                $this->movieApi->deleteHistoryByIdAndDate(
                    $movieId,
                    $userId,
                    empty($date) === true ? null : Date::createFromString($date),
                );
            }
        }

        return Response::createNoContent();
    }

    public function getPlayed(Request $request) : Response
    {
        $requestData = $this->playedRequestMapper->mapRequest($request);

        $playedEntries = $this->movieApi->fetchPlayedMoviesPaginated(
            $requestData->getRequestedUserId(),
            $requestData->getLimit(),
            $requestData->getPage(),
            $requestData->getSearchTerm(),
            $requestData->getSortBy(),
            $requestData->getSortOrder(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
            null,
            null,
            null,
        );

        $watchDates = $this->movieApi->fetchWatchDatesForMovies($requestData->getRequestedUserId(), $playedEntries);

        $watchlistCount = $this->movieApi->fetchPlayedMoviesCount(
            $requestData->getRequestedUserId(),
            $requestData->getSearchTerm(),
            $requestData->getReleaseYear(),
            $requestData->getLanguage(),
            $requestData->getGenre(),
            null,
            null,
            null,
        );

        $paginationElements = $this->paginationElementsCalculator->createPaginationElements(
            $watchlistCount,
            $requestData->getLimit(),
            $requestData->getPage(),
        );

        return Response::createJson(
            Json::encode([
                'played' => $this->playedResponseMapper->mapPlayedEntries($playedEntries, $watchDates),
                'currentPage' => $paginationElements->getCurrentPage(),
                'maxPage' => $paginationElements->getMaxPage(),
            ]),
        );
    }

    public function updatePlayed(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $playedUpdates = Json::decode($request->getBody());

        foreach ($playedUpdates as $playedUpdate) {
            $movieId = (int)$playedUpdate['movaryId'];
            $watchDates = $playedUpdate['watchDates'] ?? [];

            foreach ($watchDates as $watchDate) {
                $this->movieApi->replaceHistoryForMovieByDate(
                    $movieId,
                    $userId,
                    $watchDate['watchedAt'] !== null ? Date::createFromString($watchDate['watchedAt']) : null,
                    $watchDate['plays'],
                    $watchDate['comment'],
                );
            }
        }

        return Response::createNoContent();
    }
}
