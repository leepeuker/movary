<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Domain\Movie\History\PlayCountConflict;
use Movary\Domain\Movie\MovieApi;
use Movary\HttpController\Api\Exception\InvalidApiRequest;
use Movary\HttpController\Api\RequestMapper\PlayedRequestMapper;
use Movary\HttpController\Api\RequestMapper\RequestMapper;
use Movary\HttpController\Api\ResponseMapper\PlayedResponseMapper;
use Movary\Service\PaginationElementsCalculator;
use Movary\Util\Json;
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
        $playedAdditions = $this->playedRequestMapper->mapAdditions($request);

        foreach ($playedAdditions as $playAddition) {
            foreach ($playAddition['watchDates'] as $watchDate) {
                $this->movieApi->addPlaysForMovieOnDate(
                    $playAddition['movieId'],
                    $userId,
                    $watchDate['watchedAt'],
                    $watchDate['plays'],
                    comment: $watchDate['comment'],
                );
            }
        }

        return Response::createNoContent();
    }

    public function deleteFromPlayed(Request $request) : Response
    {
        $userId = $this->requestMapper->mapUsernameFromRoute($request)->getId();
        $playedDeletions = $this->playedRequestMapper->mapDeletions($request);

        foreach ($playedDeletions as $playedDeletion) {
            if (count($playedDeletion['watchDates']) === 0) {
                $this->movieApi->deleteHistoryById(
                    $playedDeletion['movieId'],
                    $userId,
                );

                continue;
            }

            foreach ($playedDeletion['watchDates'] as $date) {
                $this->movieApi->deleteHistoryByIdAndDate(
                    $playedDeletion['movieId'],
                    $userId,
                    $date,
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
        $playedUpdates = $this->playedRequestMapper->mapUpdates($request);

        foreach ($playedUpdates as $playedUpdate) {
            foreach ($playedUpdate['watchDates'] as $watchDate) {
                try {
                    $this->movieApi->replaceHistoryForMovieByDate(
                        $playedUpdate['movieId'],
                        $userId,
                        $watchDate['watchedAt'],
                        $watchDate['plays'],
                        comment: $watchDate['comment'],
                        commentProvided: $watchDate['commentProvided'],
                    );
                } catch (PlayCountConflict) {
                    throw InvalidApiRequest::playCountConflict();
                }
            }
        }

        return Response::createNoContent();
    }
}
