<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Api\Tmdb\Exception\TmdbResourceNotFound;
use Movary\HttpController\Api\RequestMapper\MovieAddRequestMapper;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieAddController
{
    public function __construct(
        private readonly SyncMovie $tmdbSyncMovie,
        private readonly MovieAddRequestMapper $requestMapper,
    ) {
    }

    public function addMovie(Request $request) : Response
    {
        $requestData = $this->requestMapper->mapRequest($request);

        try {
            $movie = $this->tmdbSyncMovie->syncMovie($requestData->getTmdbId());
        } catch (TmdbResourceNotFound) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'ProviderIdNotFound',
                    'message' => 'External provider found no movie for submitted id',
                ]),
                [Header::createContentTypeJson()],
            );
        }

        return Response::createJson(
            Json::encode(
                [
                    'movaryId' => $movie->getId(),
                ],
            ),
        );
    }
}
