<?php declare(strict_types=1);

namespace Movary\HttpController\Api;

use Movary\Api\Tmdb\Exception\TmdbResourceNotFound;
use Movary\Service\Tmdb\SyncMovie;
use Movary\Util\Json;
use Movary\ValueObject\Http\Header;
use Movary\ValueObject\Http\Request;
use Movary\ValueObject\Http\Response;

class MovieAddController
{
    public function __construct(
        private readonly SyncMovie $tmdbSyncMovie,
    ) {
    }

    public function addMovie(Request $request) : Response
    {
        $requestBody = Json::decode($request->getBody());

        if (isset($requestBody['tmdbId']) === false) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'MissingProviderId',
                    'message' => 'No external provider id submitted',
                ]),
                [Header::createContentTypeJson()],
            );
        }

        try {
            $movie = $this->tmdbSyncMovie->syncMovie($requestBody['tmdbId']);
        } catch (TmdbResourceNotFound) {
            return Response::createBadRequest(
                Json::encode([
                    'error' => 'ProviderIdNotFound',
                    'message' => 'External provider found no movie with submitted provider id',
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
